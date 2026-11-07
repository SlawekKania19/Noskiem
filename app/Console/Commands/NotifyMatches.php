<?php

namespace App\Console\Commands;

use App\Models\MatchRequest;
use App\Services\PhotoMatchNotifier;
use Illuminate\Console\Command;

// ---------------------------
// Realizuje zlecenia "poszukaj podobnych i napisz" (match_requests) — odpalana ze
// schedulera co minutę. Zlecenie czeka, dopóki match:embed nie policzy wszystkich
// zdjęć zgłoszenia; samo wyszukiwanie jest lekkie (numpy, ułamek sekundy).
// Respektuje MATCH_ENABLED i MATCH_PAUSED (pauza wstrzymuje też maile).
// ---------------------------
class NotifyMatches extends Command
{
    protected $signature = 'match:notify';

    protected $description = 'Wysyła autorom nowych zgłoszeń maile z podobnymi ogłoszeniami (po zdjęciach)';

    public function handle(PhotoMatchNotifier $notifier): int
    {
        if (! config('noskiem_match.enabled') || config('noskiem_match.queue.paused')) {
            return self::SUCCESS;
        }

        $stats = [];

        MatchRequest::where('status', 'queued')
            ->with('animalEdit')
            ->orderBy('id')
            ->limit(config('noskiem_match.notify.batch'))
            ->get()
            ->each(function (MatchRequest $request) use ($notifier, &$stats) {
                $status = $notifier->process($request);
                $stats[$status] = ($stats[$status] ?? 0) + 1;
            });

        if ($stats !== []) {
            $this->info(collect($stats)->map(fn ($n, $s) => "{$s}: {$n}")->implode(', '));
        }

        return self::SUCCESS;
    }
}
