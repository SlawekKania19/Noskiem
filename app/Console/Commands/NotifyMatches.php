<?php

namespace App\Console\Commands;

use App\Models\MatchRequest;
use App\Services\PhotoMatchNotifier;
use Illuminate\Console\Command;

// ---------------------------
// Realizuje zlecenia "poszukaj podobnych i napisz" (match_requests) — odpalana ze
// schedulera co minutę. Zlecenie czeka na zatwierdzenie zgłoszenia przez moderatora
// i na wektory wszystkich zdjęć (match:embed); samo wyszukiwanie jest lekkie (numpy).
// Respektuje MATCH_ENABLED i pauzę (panel „Dopasowanie AI” / MATCH_PAUSED — wstrzymuje też maile).
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
            // ** Pomijamy zgłoszenia czekające na moderację (mail dopiero po zatwierdzeniu) —
            // inaczej przy kolejce moderacji > batch zajmowałyby całą partię i blokowały
            // zatwierdzone. Wyjątek: zbyt stare, żeby process() oznaczył je jako wygasłe
            ->whereDoesntHave('animalEdit', fn ($q) => $q
                ->where('mod_status', 'pending')
                ->where('match_requests.created_at', '>=', now()->subDays(config('noskiem_match.notify.pending_ttl_days'))))
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
