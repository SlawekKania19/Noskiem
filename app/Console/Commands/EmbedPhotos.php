<?php

namespace App\Console\Commands;

use App\Services\PhotoEmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

// ---------------------------
// Worker kolejki wektorów zdjęć (dopasowania po zdjęciach). Odpalany ze schedulera
// co minutę (routes/console.php); bierze partię zdjęć bez wektora, uruchamia Pythona
// (model ładowany raz na partię) i kończy. Regulacja: config/noskiem_match.php (.env).
//
// Backfill istniejących zdjęć = ta sama komenda, np.:
//   php artisan match:embed --limit=500 --ignore-hours
// Wznawialne: policzone zdjęcia są pomijane, błędne też (chyba że --retry-failed).
// ---------------------------
class EmbedPhotos extends Command
{
    protected $signature = 'match:embed
        {--limit= : Maks. zdjęć w tym uruchomieniu (domyślnie MATCH_BATCH_LIMIT)}
        {--ignore-hours : Pracuj także poza oknem MATCH_HOURS (np. ręczny backfill)}
        {--retry-failed : Spróbuj ponownie zdjęć, które wcześniej zwróciły błąd}
        {--dry-run : Tylko pokaż, ile zdjęć czeka}';

    protected $description = 'Liczy wektory zdjęć do dopasowań (noskiem_match) — partia z kolejki';

    public function handle(PhotoEmbeddingService $service): int
    {
        $modelVersion = $service->modelVersion();
        $retryFailed = (bool) $this->option('retry-failed');
        $waiting = $service->pendingQuery($modelVersion, $retryFailed)->count();

        if ($this->option('dry-run')) {
            $this->info("Czeka na wektor ({$modelVersion}): {$waiting}");
            $reason = $service->blockedReason();
            $this->line($reason ? "Worker teraz NIE ruszy: {$reason}" : 'Worker teraz może pracować.');

            return self::SUCCESS;
        }

        $reason = $service->blockedReason();

        // ** --ignore-hours pomija tylko okno godzin — wyłączony moduł i pauza obowiązują zawsze
        if ($reason !== null && ! ($this->option('ignore-hours') && str_starts_with($reason, 'poza oknem'))) {
            $this->line("Pomijam: {$reason}. W kolejce: {$waiting}");

            return self::SUCCESS;
        }

        if ($waiting === 0) {
            return self::SUCCESS;
        }

        // ** Blokada — scheduler i ręczny backfill nie mogą liczyć równolegle
        // (dwa procesy z modelem w pamięci przekroczyłyby limit RAM hostingu)
        $lock = Cache::lock('match:embed', config('noskiem_match.queue.max_seconds') + 600);

        if (! $lock->get()) {
            $this->line('Inny worker już pracuje — pomijam.');

            return self::SUCCESS;
        }

        try {
            $limit = (int) ($this->option('limit') ?: config('noskiem_match.queue.batch_limit'));
            $photos = $service->pendingQuery($modelVersion, $retryFailed)->limit(max(1, $limit))->get();

            $stats = $service->run($photos, $modelVersion);
        } finally {
            $lock->release();
        }

        $this->info(sprintf(
            'Policzone: %d, błędy: %d, w kolejce zostało: %d, ładowanie modelu: %s ms, RAM szczyt: %s MB%s',
            $stats['processed'],
            $stats['failed'],
            max(0, $waiting - $stats['processed'] - $stats['failed']),
            $stats['load_ms'] ?? '?',
            $stats['peak_rss_mb'] ?? '?',
            $stats['stopped'] ? " (przerwane: {$stats['stopped']})" : '',
        ));

        if ($stats['crashed']) {
            $this->error('Proces Pythona zakończył się nieprawidłowo. stderr: '.$stats['stderr']);
            report(new \RuntimeException('match:embed — proces Pythona przerwany: '.$stats['stderr']));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
