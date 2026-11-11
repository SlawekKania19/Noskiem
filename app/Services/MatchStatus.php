<?php

namespace App\Services;

use App\Models\MatchRequest;
use App\Models\MatchSearch;
use App\Models\PhotoEmbedding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

// ---------------------------
// Stan dopasowań po zdjęciach do panelu "Dopasowanie AI": worker, kolejka, maile.
// Znaczniki workera (ostatnie uruchomienie, trwająca/ostatnia partia) zapisuje
// match:embed w cache - w bazie nie ma śladu uruchomień, które nic nie policzyły.
// ---------------------------

class MatchStatus
{
    // ** Klucze cache zapisywane przez match:embed (App\Console\Commands\EmbedPhotos)
    public const LAST_RUN = 'match:embed:last_run';
    public const RUNNING = 'match:embed:running';
    public const LAST_BATCH = 'match:embed:last_batch';

    // ** Po ilu minutach bez uruchomienia workera uznajemy, że scheduler (cron) nie działa
    public const STALE_MINUTES = 3;

    public function __construct(private PhotoEmbeddingService $embeddings) {}

    // ---------------------------
    // Znaczniki zapisywane przez workera
    // ---------------------------

    // Każde uruchomienie match:embed (także gdy kolejka pusta albo pauza) - "puls" schedulera
    public static function markRun(): void
    {
        Cache::forever(self::LAST_RUN, now()->toIso8601String());
    }

    // Początek partii - TTL jak blokada workera, żeby po zabitym procesie znacznik sam wygasł
    public static function markRunning(int $photos): void
    {
        Cache::put(self::RUNNING, [
            'started_at' => now()->toIso8601String(),
            'photos' => $photos,
        ], config('noskiem_match.queue.max_seconds') + 600);
    }

    // Koniec partii - zapisujemy wynik do podglądu w panelu
    public static function markFinished(array $stats, int $photos): void
    {
        Cache::forget(self::RUNNING);

        Cache::forever(self::LAST_BATCH, [
            'finished_at' => now()->toIso8601String(),
            'photos' => $photos,
            'processed' => $stats['processed'],
            'failed' => $stats['failed'],
            'load_ms' => $stats['load_ms'],
            'peak_rss_mb' => $stats['peak_rss_mb'],
            'crashed' => $stats['crashed'],
        ]);
    }

    // ---------------------------
    // Odczyt stanu dla panelu
    // ---------------------------
    public function snapshot(): array
    {
        $mv = $this->embeddings->modelVersion();
        $lastRun = Cache::get(self::LAST_RUN);
        $running = Cache::get(self::RUNNING);
        $lastRunAt = $lastRun ? Carbon::parse($lastRun) : null;

        $current = PhotoEmbedding::where('model_version', $mv);

        return [
            'model_version' => $mv,
            'enabled' => (bool) config('noskiem_match.enabled'),
            'python_found' => is_file((string) config('noskiem_match.python')),
            'blocked_reason' => $this->embeddings->blockedReason(),

            'last_run_at' => $lastRunAt,
            // ** Podczas długiej partii scheduler pomija kolejne uruchomienia (withoutOverlapping),
            // więc brak "pulsu" przy trwającej partii nie oznacza awarii crona
            'scheduler_stale' => ! $running
                && (! $lastRunAt || $lastRunAt->lt(now()->subMinutes(self::STALE_MINUTES))),
            'running' => $running ? [
                'started_at' => Carbon::parse($running['started_at']),
                'photos' => $running['photos'],
            ] : null,
            'last_batch' => ($batch = Cache::get(self::LAST_BATCH))
                ? ['finished_at' => Carbon::parse($batch['finished_at'])] + $batch
                : null,

            'queue' => [
                'pending' => $this->embeddings->pendingQuery($mv)->count(),
                'failed' => $this->failedCount(),
                'done' => (clone $current)->whereNotNull('vector')->count(),
                // ** Średni czas liczenia z ostatnich 50 zdjęć (ms)
                'avg_ms' => (int) PhotoEmbedding::query()
                    ->fromSub((clone $current)->whereNotNull('duration_ms')->latest('id')->limit(50)->select('duration_ms'), 'recent')
                    ->avg('duration_ms'),
                'last_embedded_at' => (clone $current)->whereNotNull('vector')->max('updated_at'),
            ],

            // ** Zlecenia maili z ostatnich 7 dni wg statusu (queued, notified, no_matches, ...)
            'requests' => MatchRequest::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('status, COUNT(*) as n')
                ->groupBy('status')
                ->pluck('n', 'status')
                ->map(fn ($n) => (int) $n)
                ->all(),

            'last_search_at' => MatchSearch::max('created_at'),
        ];
    }

    // ---------------------------
    // "Ponów błędne": usuwamy wpisy z błędem dla bieżącej wersji modelu - zdjęcia
    // wracają do zwykłej kolejki i worker policzy je w następnych partiach.
    // Bez uruchamiania Pythona w żądaniu WWW (partia trwa nawet kilka minut).
    // ---------------------------
    public function retryFailed(): int
    {
        return $this->failedQuery()->delete();
    }

    public function failedCount(): int
    {
        return $this->failedQuery()->count();
    }

    // ** Wpisy z błędem (bez wektora) dla bieżącej wersji modelu
    protected function failedQuery()
    {
        return PhotoEmbedding::where('model_version', $this->embeddings->modelVersion())->whereNull('vector');
    }
}
