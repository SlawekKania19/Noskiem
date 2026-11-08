<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\Breed;
use App\Models\PhotoEmbedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

// ---------------------------
// Wyszukiwanie podobnych zwierząt po zdjęciach.
// PHP: twarde filtry puli kandydatów (SQL), zebranie wektorów, metadane rasa/kolory.
// Python (cli.py search, tylko numpy): max podobieństwa po zdjęciach, progi, bonusy, ranking.
// Wynik zawiera top log_floor_n kandydatów (także poniżej progu) z flagą "shown" —
// do wyświetlenia/maila idą tylko shown, reszta jest do logów (etap 4).
// ---------------------------

class PhotoMatchService
{
    public function __construct(private PhotoEmbeddingService $embeddings) {}

    /**
     * Szuka podobnych ogłoszeń dla zgłoszenia (AnimalEdit — nowe, przed moderacją)
     * albo zatwierdzonego ogłoszenia (Animal).
     *
     * status:
     *  - ok          — ranking policzony (results może być pusty = "brak podobnych")
     *  - no_photos   — zgłoszenie bez zdjęć, nie ma czego porównywać
     *  - pending     — część zdjęć zapytania nie ma jeszcze wektora (worker nie zdążył)
     *  - no_embedding — żadne zdjęcie zapytania nie dało się policzyć (np. uszkodzone pliki)
     */
    public function search(Animal|AnimalEdit $query): array
    {
        $cfg = config('noskiem_match.search');
        $mv = $this->embeddings->modelVersion();

        $base = ['model_version' => $mv, 'params' => $this->params($cfg), 'results' => [],
            'n_candidates' => 0, 'n_compared' => 0, 'n_pending_embedding' => 0];

        // ---------------------------
        // Wektory zapytania
        // ---------------------------
        $photoIds = $query->photos()->whereNull('sighting_id')->orderBy('id')->pluck('id');

        if ($photoIds->isEmpty()) {
            return ['status' => 'no_photos'] + $base;
        }

        $rows = PhotoEmbedding::whereIn('photo_id', $photoIds)->where('model_version', $mv)
            ->orderBy('photo_id')->get(['photo_id', 'vector', 'dim']);

        if ($rows->count() < $photoIds->count()) {
            return ['status' => 'pending'] + $base;
        }

        $queryVectors = $rows->whereNotNull('vector');

        if ($queryVectors->isEmpty()) {
            return ['status' => 'no_embedding'] + $base;
        }

        $dim = (int) $queryVectors->first()->dim;

        // ---------------------------
        // Pula kandydatów (twarde filtry) + ich wektory
        // ---------------------------
        $candidates = $this->candidates($query, $cfg['filters']);
        $colors = $this->colorIds($candidates->keys()->all());
        $neutral = Breed::whereIn('breed_pl', $cfg['neutral_breeds'])->pluck('id')->all();
        $breedOf = fn ($id) => in_array($id, $neutral) ? null : $id;

        // ** Plik binarny: najpierw wiersze zapytania, potem kandydaci po kolei.
        // Wektory kandydatów idą do pliku strumieniowo (partiami), nie przez pamięć PHP —
        // przy dziesiątkach tysięcy zdjęć to setki MB
        $file = tempnam(sys_get_temp_dir(), 'nmatch_');
        $handle = fopen($file, 'wb');

        try {
            foreach ($queryVectors as $row) {
                fwrite($handle, $row->vector);
            }

            $rowsByAnimal = $this->writeCandidateVectors($handle, $candidates->keys()->all(), $mv, $dim, $queryVectors->count());
            $payloadCandidates = [];
            $pending = 0;

            foreach ($candidates as $id => $animal) {
                // ** Kandydat bez żadnego wektora (worker jeszcze nie policzył) — nie porównujemy,
                // tylko liczymy, żeby było widać, że pula nie jest kompletna
                if (! isset($rowsByAnimal[$id])) {
                    $pending++;

                    continue;
                }

                $payloadCandidates[] = [
                    'listing_id' => $id,
                    'rows'       => $rowsByAnimal[$id],
                    'breed_id'   => $breedOf($animal->breed_id),
                    'color_ids'  => $colors[$id] ?? [],
                ];
            }

            fclose($handle);
            $handle = null;

            $out = $payloadCandidates === []
                ? ['results' => [], 'n_compared' => 0]
                : $this->runPython([
                    'dim'          => $dim,
                    'vectors_file' => $file,
                    'query_rows'   => [0, $queryVectors->count()],
                    'query'        => [
                        'breed_id'  => $breedOf($query->breed_id),
                        'color_ids' => $query->colors()->pluck('colors.id')->all(),
                    ],
                    'candidates'   => $payloadCandidates,
                    'params'       => $base['params'],
                ], $mv);
        } finally {
            if ($handle) {
                fclose($handle);
            }
            @unlink($file);
        }

        // ** Odległość do UI/maila (twardy filtr promienia działa niezależnie)
        $results = array_map(function (array $r) use ($candidates, $query) {
            $c = $candidates[$r['listing_id']];
            $r['distance_km'] = round($this->distanceKm($query->latitude, $query->longitude, $c->latitude, $c->longitude), 1);

            return $r;
        }, $out['results']);

        return array_merge($base, [
            'status'              => 'ok',
            'results'             => $results,
            'n_candidates'        => $candidates->count(),
            'n_compared'          => $out['n_compared'],
            'n_pending_embedding' => $pending,
        ]);
    }

    // ** Parametry wyszukiwania przekazywane do Pythona i zapisywane w logu —
    // w logu musi być dokładnie to, czego użyto
    protected function params(array $cfg): array
    {
        return [
            'top_n'              => $cfg['top_n'],
            'threshold_possible' => $cfg['threshold_possible'],
            'threshold_strong'   => $cfg['threshold_strong'],
            'log_floor_n'        => $cfg['log_floor_n'],
            'soft_weights'       => $cfg['soft_weights'],
            'neutral_breeds'     => $cfg['neutral_breeds'],
            'filters'            => $cfg['filters'],
        ];
    }

    // ---------------------------
    // Twarde filtry — zwraca [animal_id => Animal] (tylko potrzebne kolumny)
    // ---------------------------
    protected function candidates(Animal|AnimalEdit $query, array $f)
    {
        $radius = (float) $f['radius_km'];
        $days = (int) $f['date_window_days'];

        $q = Animal::query()
            ->select(['id', 'status', 'species_id', 'breed_id', 'latitude', 'longitude', 'date_event'])
            ->where('mod_status', 'approved')
            ->when($f['opposite_status'], fn (Builder $q) => $q->where('status', $query->status === 'lost' ? 'found' : 'lost'))
            ->when($f['same_species'], fn (Builder $q) => $q->where('species_id', $query->species_id))
            // ** Nie porównujemy ogłoszenia samego ze sobą (edycja istniejącego / zapytanie z Animal)
            ->when($this->ownAnimalId($query), fn (Builder $q, $id) => $q->whereKeyNot($id))
            ->when($f['exclude_same_email'] && filled($query->contact_email),
                fn (Builder $q) => $q->whereRaw('LOWER(contact_email) <> ?', [mb_strtolower($query->contact_email)]))
            ->when($days > 0 && $query->date_event, fn (Builder $q) => $q->whereBetween('date_event', [
                $query->date_event->copy()->subDays($days)->toDateString(),
                $query->date_event->copy()->addDays($days)->toDateString(),
            ]));

        // ** Promień: w SQL tylko prostokąt (szybki, działa na każdej bazie),
        // dokładna odległość po stronie PHP
        if ($radius > 0) {
            $dLat = $radius / 111.0;
            $dLng = $radius / (111.0 * max(0.01, cos(deg2rad($query->latitude))));
            $q->whereBetween('latitude', [$query->latitude - $dLat, $query->latitude + $dLat])
                ->whereBetween('longitude', [$query->longitude - $dLng, $query->longitude + $dLng]);
        }

        return $q->get()
            ->when($radius > 0, fn ($c) => $c->filter(
                fn (Animal $a) => $this->distanceKm($query->latitude, $query->longitude, $a->latitude, $a->longitude) <= $radius))
            ->keyBy('id');
    }

    protected function ownAnimalId(Animal|AnimalEdit $query): ?int
    {
        return $query instanceof Animal ? $query->id : $query->animal_id;
    }

    /**
     * Zapisuje wektory kandydatów do pliku (tylko udane, bieżącej wersji, zgodny wymiar)
     * i zwraca [animal_id => [pierwszy_wiersz, liczba_wierszy]]. Sortowanie po animal_id
     * gwarantuje, że wiersze jednego ogłoszenia leżą w pliku obok siebie.
     *
     * @param  resource  $handle
     */
    protected function writeCandidateVectors($handle, array $animalIds, string $mv, int $dim, int $offset): array
    {
        $rows = [];

        foreach (array_chunk($animalIds, 1000) as $chunk) {
            DB::table('photo_embeddings')
                ->join('photos', 'photos.id', '=', 'photo_embeddings.photo_id')
                ->whereIn('photos.animal_id', $chunk)
                ->whereNull('photos.sighting_id')
                ->where('photo_embeddings.model_version', $mv)
                ->whereNotNull('photo_embeddings.vector')
                ->where('photo_embeddings.dim', $dim)
                ->orderBy('photos.animal_id')->orderBy('photos.id')
                ->select(['photos.animal_id', 'photo_embeddings.vector'])
                ->lazy(500)
                ->each(function ($row) use ($handle, &$rows, &$offset) {
                    fwrite($handle, $row->vector);
                    $rows[$row->animal_id] ??= [$offset, 0];
                    $rows[$row->animal_id][1]++;
                    $offset++;
                });
        }

        return $rows;
    }

    protected function colorIds(array $animalIds): array
    {
        $out = [];

        foreach (array_chunk($animalIds, 1000) as $chunk) {
            DB::table('animal_color')->whereIn('animal_id', $chunk)->get(['animal_id', 'color_id'])
                ->each(function ($row) use (&$out) {
                    $out[$row->animal_id][] = (int) $row->color_id;
                });
        }

        return $out;
    }

    protected function runPython(array $request, string $mv): array
    {
        $result = Process::path(config('noskiem_match.module_path'))
            ->env(['PYTHONIOENCODING' => 'utf-8', 'OMP_NUM_THREADS' => '1'])
            ->input(json_encode($request, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))
            ->timeout(config('noskiem_match.search.timeout'))
            ->run([config('noskiem_match.python'), 'cli.py', 'search']);

        $out = json_decode($result->output(), true);

        if (! $result->successful() || ($out['status'] ?? null) !== 'ok') {
            throw new \RuntimeException('match search — błąd Pythona: '.mb_substr($result->errorOutput(), -1000));
        }

        if (($out['model_version'] ?? null) !== $mv) {
            throw new \RuntimeException("Niezgodna model_version: {$out['model_version']} ≠ {$mv}");
        }

        return $out;
    }

    // ** Odległość po kole wielkim (haversine), km
    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
