<?php

namespace App\Services;

use App\Mail\MatchCandidatesFound;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchRequest;
use App\Models\MatchSearch;
use App\Models\ModerationLog;
use App\Models\Photo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

// ---------------------------
// Zlecenia "poszukaj podobnych i napisz do autora" (tabela match_requests).
// queue() — po potwierdzeniu e-maila nowego zgłoszenia (AnimalEditController::confirmEmail).
// process() — z php artisan match:notify: po ZATWIERDZENIU zgłoszenia przez moderatora,
// gdy zdjęcia mają już wektory (liczy je match:embed od razu po potwierdzeniu e-maila),
// szuka kandydatów i wysyła mail TYLKO gdy coś przekracza próg.
// ---------------------------

class PhotoMatchNotifier
{
    public function __construct(private PhotoMatchService $matcher, private MatchSearchLogger $logger) {}

    // ** Czy obiecujemy autorowi wyszukiwanie — moduł włączony i są zdjęcia do porównania.
    // Ten sam warunek decyduje o tekstach komunikatów, żeby nie obiecywać czegoś, czego nie zrobimy
    public static function willSearch(AnimalEdit $edit): bool
    {
        return config('noskiem_match.enabled') && $edit->photos()->exists();
    }

    public function queue(AnimalEdit $edit): ?MatchRequest
    {
        if (! self::willSearch($edit)) {
            return null;
        }

        return MatchRequest::firstOrCreate(['animal_edit_id' => $edit->id], ['status' => 'queued']);
    }

    // ---------------------------
    // Realizacja jednego zlecenia — zwraca jego status po przetworzeniu
    // ---------------------------
    public function process(MatchRequest $request): string
    {
        $edit = $request->animalEdit;

        // ** Mail idzie dopiero PO zatwierdzeniu — moderator decyduje, czy autor w ogóle coś
        // dostanie (spam, odrzucone). Wektory liczą się wcześniej, więc po zatwierdzeniu
        // mail wychodzi w ciągu minuty. Oczekujące czekają, ale nie w nieskończoność
        if ($edit?->mod_status === 'pending') {
            return $request->created_at->lt(now()->subDays(config('noskiem_match.notify.pending_ttl_days')))
                ? $this->finish($request, 'expired')
                : 'queued';
        }

        [$query, $approvedAt] = $this->resolveApproved($edit);

        if ($query === null) {
            // Odrzucone (spam / błędne zgłoszenie), usunięte albo nie da się ustalić ogłoszenia
            return $this->finish($request, 'skipped');
        }

        // ** Termin liczony od zatwierdzenia — wolna moderacja (np. weekend) nie może
        // sprawić, że mail przepadnie; liczy się tylko czekanie na wektory po zatwierdzeniu
        if ($approvedAt->lt(now()->subHours(config('noskiem_match.notify.ttl_hours')))) {
            return $this->finish($request, 'expired');
        }

        try {
            $out = $this->matcher->search($query);

            // ** Zdjęcia jeszcze w kolejce match:embed — spróbujemy w kolejnym uruchomieniu
            if ($out['status'] === 'pending') {
                return 'queued';
            }

            if ($out['status'] !== 'ok') {
                return $this->finish($request, $out['status']);
            }

            // ** Log PRZED decyzją o mailu — także wyszukiwania bez wyników ponad progiem
            // (z kandydatami poniżej progu) są potrzebne do kalibracji
            $search = $this->logger->record($out, $query, 'notify', $request);

            $shown = array_values(array_filter($out['results'], fn ($r) => $r['shown']));

            if ($shown === []) {
                return $this->finish($request, 'no_matches', 0);
            }

            Mail::to($edit->contact_email, $edit->contact_name)
                ->send(new MatchCandidatesFound($edit, $this->mailItems($shown, $search), $out['params']));

            return $this->finish($request, 'notified', count($shown));
        } catch (\Throwable $e) {
            report($e);
            $attempts = $request->attempts + 1;
            $request->update(['attempts' => $attempts, 'last_error' => mb_substr($e->getMessage(), 0, 500)]);

            return $attempts >= config('noskiem_match.notify.max_attempts')
                ? $this->finish($request, 'failed')
                : 'queued';
        }
    }

    /**
     * Zatwierdzone ogłoszenie i moment zatwierdzenia. Po zatwierdzeniu zdjęcia są
     * przepięte na nowy Animal (ModerationService::approve), a animal_edits.animal_id
     * zostaje null — ogłoszenie znajdujemy przez log moderacji.
     *
     * @return array{0: ?Animal, 1: ?\Illuminate\Support\Carbon}
     */
    protected function resolveApproved(?AnimalEdit $edit): array
    {
        if ($edit?->mod_status !== 'approved') {
            return [null, null];
        }

        $log = ModerationLog::where('animal_edit_id', $edit->id)
            ->where('action', 'approved')->latest('id')->first();

        $animal = Animal::find($edit->animal_id ?? $log?->animal_id);

        // ** Brak wpisu w logu (stare dane) — liczymy od ostatniej zmiany zgłoszenia (zatwierdzenia)
        return [$animal, $log?->created_at ?? $edit->updated_at];
    }

    // ** Dane do maila jako zwykłe tablice — widok nie odpytuje bazy. Linki przez
    // podpisany endpoint kliknięć (MatchClickController) — zapis pozycji, potem przekierowanie
    protected function mailItems(array $shown, MatchSearch $search): array
    {
        $mv = app(PhotoEmbeddingService::class)->modelVersion();
        $animals = Animal::with(['species', 'breed', 'city', 'voivodeship'])
            ->findMany(array_column($shown, 'listing_id'))->keyBy('id');

        return collect($shown)
            ->filter(fn ($r) => isset($animals[$r['listing_id']]))
            ->map(function (array $r) use ($animals, $mv, $search) {
                $animal = $animals[$r['listing_id']];

                return [
                    'title'       => $animal->generated_title,
                    // ** Bez wygasania — maile czyta się też po tygodniach
                    'url'         => URL::signedRoute('matches.click', ['matchSearch' => $search->id, 'position' => $r['position']]),
                    'photo_url'   => $this->bestPhotoUrl($animal, $r['best_photo_index'], $mv),
                    'level'       => $r['level'],
                    'location'    => $animal->location_text,
                    'date'        => $animal->date_event?->format('d.m.Y'),
                    'distance_km' => $r['distance_km'],
                ];
            })
            ->values()
            ->all();
    }

    // ** Zdjęcie kandydata, które najbardziej przypomina zgłoszenie. best_photo_index to
    // indeks w tej samej kolejności, w jakiej PhotoMatchService wysłał wektory do Pythona
    // (zdjęcia z udanym wektorem, po id). Fallback: zdjęcie główne
    protected function bestPhotoUrl(Animal $animal, int $index, string $mv): ?string
    {
        $photo = $animal->photos()
            ->whereNull('sighting_id')
            ->whereHas('embeddings', fn ($q) => $q->where('model_version', $mv)->whereNotNull('vector'))
            ->orderBy('id')->skip($index)->first()
            ?? $animal->photos()->orderByDesc('is_main')->orderBy('id')->first();

        return $photo instanceof Photo ? asset('storage/'.$photo->path) : null;
    }

    protected function finish(MatchRequest $request, string $status, ?int $shown = null): string
    {
        $request->update(['status' => $status, 'results_shown' => $shown, 'processed_at' => now()]);

        return $status;
    }
}
