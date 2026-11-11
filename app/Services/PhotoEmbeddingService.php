<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\PhotoEmbedding;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

// ---------------------------
// Kolejka liczenia wektorów zdjęć (dopasowania po zdjęciach, moduł noskiem_match/).
// "Kolejka" to po prostu zdjęcia bez wektora dla bieżącej model_version - stan w bazie,
// więc pauza, okno godzin czy przerwany proces niczego nie gubią.
// PHP wybiera zdjęcia i zapisuje wyniki; Python (cli.py embed) tylko liczy:
// dostaje JSONL {photo_id, path} na stdin, zwraca JSONL z wektorami na stdout.
// Wywoływany z php artisan match:embed (scheduler co minutę).
// ---------------------------

class PhotoEmbeddingService
{
    // ** Wersja modelu z noskiem_match/config.json - jedno źródło dla Pythona i PHP
    public function modelVersion(): string
    {
        $cfg = json_decode(
            file_get_contents(config('noskiem_match.module_path').'/config.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $cfg['model_version'];
    }

    // ---------------------------
    // Czy worker może teraz pracować - zwraca null albo powód, dla którego nie
    // ---------------------------
    public function blockedReason(?CarbonImmutable $now = null): ?string
    {
        if (! config('noskiem_match.enabled')) {
            return 'moduł wyłączony (MATCH_ENABLED)';
        }

        if (config('noskiem_match.queue.paused')) {
            return 'kolejka wstrzymana (panel „Dopasowanie AI” lub MATCH_PAUSED)';
        }

        $hours = trim((string) config('noskiem_match.queue.hours'));

        if ($hours !== '' && ! $this->withinHours($hours, $now)) {
            return "poza oknem godzin ({$hours})";
        }

        return null;
    }

    // ** Okno "HH:MM-HH:MM" w strefie z konfiguracji; może przechodzić przez północ (22:00-06:00)
    public function withinHours(string $window, ?CarbonImmutable $now = null): bool
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', $window, $m)) {
            // ** Literówka w .env nie może po cichu zatrzymać kolejki na zawsze - wolimy pracować
            report(new \InvalidArgumentException("Niepoprawne MATCH_HOURS: {$window}"));

            return true;
        }

        $now = ($now ?? CarbonImmutable::now())->setTimezone(config('noskiem_match.queue.timezone'));
        $minute = $now->hour * 60 + $now->minute;
        $from = (int) $m[1] * 60 + (int) $m[2];
        $to = (int) $m[3] * 60 + (int) $m[4];

        return $from <= $to
            ? $minute >= $from && $minute < $to
            : $minute >= $from || $minute < $to;
    }

    // ---------------------------
    // Zdjęcia czekające na wektor
    // ---------------------------

    /**
     * Do kolejki trafiają tylko zdjęcia, które kiedykolwiek będą porównywane:
     * - zatwierdzonych ogłoszeń (kandydaci),
     * - nowych zgłoszeń z POTWIERDZONYM e-mailem, czekających na moderację (zapytania) -
     *   bez potwierdzenia nie liczymy, żeby boty i fałszywe adresy nie zajmowały CPU.
     * Zdjęcia zgłoszeń "widziałem" (sightings) na razie pomijamy.
     * Kolejność: najpierw świeże zgłoszenia (na nie ktoś czeka), potem reszta od najnowszych.
     *
     * @param  bool  $retryFailed  także zdjęcia z wcześniejszym błędem
     */
    public function pendingQuery(string $modelVersion, bool $retryFailed = false): Builder
    {
        return Photo::query()
            ->whereNull('sighting_id')
            ->where(function (Builder $q) {
                $q->whereHas('animal', fn (Builder $a) => $a->where('mod_status', 'approved'))
                    ->orWhereHas('animalEdit', fn (Builder $e) => $e
                        ->where('mod_status', 'pending')
                        ->whereNotNull('email_verified_at'));
            })
            ->whereDoesntHave('embeddings', function (Builder $q) use ($modelVersion, $retryFailed) {
                $q->where('model_version', $modelVersion)
                    ->when($retryFailed, fn (Builder $q) => $q->whereNotNull('vector'));
            })
            ->orderByRaw('animal_edit_id IS NULL')
            ->orderByDesc('id');
    }

    // ---------------------------
    // Jedno uruchomienie workera dla podanych zdjęć
    // ---------------------------

    /**
     * Uruchamia cli.py embed i zapisuje wyniki. Wyniki parsujemy z całego stdout po
     * zakończeniu procesu - także gdy Python padnie w połowie, zdjęcia policzone
     * przed awarią zostają zapisane.
     *
     * @param  Collection<int, Photo>  $photos
     * @return array{processed:int, failed:int, stopped:?string, peak_rss_mb:?float, load_ms:?int, crashed:bool, stderr:string}
     */
    public function run(Collection $photos, string $modelVersion): array
    {
        $cfg = config('noskiem_match');
        $q = $cfg['queue'];

        $input = $photos
            ->map(fn (Photo $p) => json_encode(
                ['photo_id' => $p->id, 'path' => $p->path],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ))
            ->implode("\n");

        $result = Process::path($cfg['module_path'])
            ->env([
                'HF_HUB_OFFLINE'        => $q['hf_offline'] ? '1' : '0',
                'TRANSFORMERS_VERBOSITY' => 'error',
                'PYTHONIOENCODING'      => 'utf-8',
                // ** Bez tego biblioteki numeryczne (OpenMP/MKL) też widzą 32 rdzenie serwera
                'OMP_NUM_THREADS'       => (string) $q['threads'],
                'MKL_NUM_THREADS'       => (string) $q['threads'],
            ])
            ->input($input)
            // ** Python sam kończy po max_seconds (sprawdza przed każdym zdjęciem);
            // tu tylko twardy bezpiecznik na wypadek zawieszenia
            ->timeout($q['max_seconds'] + 300)
            ->run([
                $cfg['python'], 'cli.py', 'embed',
                '--root', config('filesystems.disks.public.root'),
                '--threads', (string) $q['threads'],
                '--nice', (string) $q['nice'],
                '--max-seconds', (string) $q['max_seconds'],
            ]);

        $stats = ['processed' => 0, 'failed' => 0, 'stopped' => null, 'peak_rss_mb' => null,
            'load_ms' => null, 'crashed' => false, 'stderr' => mb_substr($result->errorOutput(), -2000)];
        $ready = false;
        $done = false;
        $handled = [];

        foreach (preg_split('/\r?\n/', trim($result->output())) as $line) {
            $event = json_decode($line, true);

            if (! is_array($event) || ! isset($event['event'])) {
                continue;
            }

            if ($event['event'] === 'ready') {
                $ready = true;
                $stats['load_ms'] = $event['load_ms'] ?? null;

                // ** Python i PHP muszą liczyć tę samą wersję - inaczej zapisalibyśmy
                // wektory pod złą etykietą i porównywali nieporównywalne
                if (($event['model_version'] ?? null) !== $modelVersion) {
                    throw new \RuntimeException("Niezgodna model_version: {$event['model_version']} ≠ {$modelVersion}");
                }
            } elseif ($event['event'] === 'result' && isset($event['photo_id'])) {
                $handled[] = (int) $event['photo_id'];
                $this->storeResult((int) $event['photo_id'], $modelVersion, $event);
                $event['status'] === 'ok' ? $stats['processed']++ : $stats['failed']++;
            } elseif ($event['event'] === 'done') {
                $done = true;
                $stats['stopped'] = $event['stopped'] ?? null;
                $stats['peak_rss_mb'] = $event['peak_rss_mb'] ?? null;
            }
        }

        // ** Proces padł w trakcie partii (np. zabity przez limit pamięci hostingu).
        // Pierwsze nieobsłużone zdjęcie to prawdopodobny winowajca - oznaczamy je błędem,
        // żeby jedno "zabójcze" zdjęcie nie blokowało kolejki w nieskończoność.
        // Gdy padło już ładowanie modelu (brak "ready") - nic nie oznaczamy, to nie wina zdjęć.
        if (! $done) {
            $stats['crashed'] = true;

            if ($ready) {
                $culprit = $photos->first(fn (Photo $p) => ! in_array($p->id, $handled, true));

                if ($culprit) {
                    $this->storeResult($culprit->id, $modelVersion, [
                        'status' => 'error',
                        'error'  => 'Proces przerwany podczas tego zdjęcia (exit '.$result->exitCode().')',
                    ]);
                    $stats['failed']++;
                }
            }
        }

        return $stats;
    }

    // ** Zapis wyniku jednego zdjęcia (upsert - ponowne liczenie nadpisuje stary błąd)
    protected function storeResult(int $photoId, string $modelVersion, array $event): void
    {
        $ok = ($event['status'] ?? null) === 'ok';
        $vector = $ok ? base64_decode($event['vector_b64'] ?? '', true) : null;

        // ** Uszkodzony wektor (zła długość) traktujemy jak błąd, nie zapisujemy śmieci
        if ($ok && ($vector === false || strlen($vector) !== 4 * (int) ($event['dim'] ?? 0))) {
            $ok = false;
            $vector = null;
            $event['error'] = 'Niepoprawny wektor w odpowiedzi Pythona';
        }

        // ** Zdjęcie mogło zostać usunięte w trakcie liczenia (np. autor skasował ogłoszenie)
        if (! Photo::whereKey($photoId)->exists()) {
            return;
        }

        PhotoEmbedding::updateOrCreate(
            ['photo_id' => $photoId, 'model_version' => $modelVersion],
            [
                'vector'      => $ok ? $vector : null,
                'dim'         => $ok ? (int) $event['dim'] : null,
                'crop_found'  => $ok ? (bool) ($event['crop_found'] ?? false) : null,
                'error'       => $ok ? null : mb_substr((string) ($event['error'] ?? 'nieznany błąd'), 0, 500),
                'duration_ms' => $event['ms'] ?? null,
            ],
        );
    }
}
