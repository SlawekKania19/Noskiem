<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Services\PhotoEmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

// ---------------------------
// Uzupełnia kadr miniaturek (photos.focus) zdjęciom, które go jeszcze nie mają:
// zdjęciom sprzed wprowadzenia kadrowania i zdjęciom, których nie liczy worker
// wektorów (np. "widziałem"). Uruchamia tylko detektor zwierząt (cli.py detect),
// bez liczenia wektorów. Nowe zdjęcia ogłoszeń dostają kadr przy match:embed.
//
//   php artisan photos:detect-focus --dry-run
//   php artisan photos:detect-focus --limit=200
// Wznawialne: zdjęcia z ustalonym kadrem (auto/manual/none) są pomijane.
// ---------------------------
class DetectPhotoFocus extends Command
{
    protected $signature = 'photos:detect-focus
        {--limit=200 : Maks. zdjęć w tym uruchomieniu}
        {--dry-run : Tylko pokaż, ile zdjęć czeka}';

    protected $description = 'Wykrywa położenie zwierzaka na zdjęciach bez kadru miniaturki';

    public function handle(PhotoEmbeddingService $service): int
    {
        $query = Photo::query()->whereNull('focus_source')->orderByDesc('id');
        $waiting = $query->count();

        if ($this->option('dry-run') || $waiting === 0) {
            $this->info("Zdjęć bez kadru: {$waiting}");

            return self::SUCCESS;
        }

        // ** Ta sama blokada co match:embed - dwa procesy z modelami w pamięci
        // przekroczyłyby limit RAM hostingu
        $lock = Cache::lock('match:embed', config('noskiem_match.queue.max_seconds') + 600);

        if (! $lock->get()) {
            $this->line('Worker match:embed właśnie pracuje - spróbuj za chwilę.');

            return self::SUCCESS;
        }

        try {
            $photos = $query->limit(max(1, (int) $this->option('limit')))->get();
            $stats = $service->detectFocus($photos);
        } finally {
            $lock->release();
        }

        $this->info(sprintf(
            'Sprawdzone: %d (zwierzę wykryte: %d), błędy: %d, zostało: %d',
            $stats['processed'],
            $stats['found'],
            $stats['failed'],
            max(0, $waiting - $stats['processed'] - $stats['failed']),
        ));

        if (! $stats['done']) {
            $this->error('Proces Pythona zakończył się nieprawidłowo. stderr: '.$stats['stderr']);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
