<?php

namespace App\Filament\Widgets;

use App\Services\MatchStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

// ---------------------------
// Stan dopasowań po zdjęciach — kafelki na górze strony "Dopasowanie AI"
// (App\Filament\Pages\MatchAi). Osobny komponent Livewire z własnym odświeżaniem,
// żeby polling nie przeszkadzał w edycji formularza ustawień.
// ---------------------------

class MatchStatusWidget extends BaseWidget
{
    // ** Tylko na stronie "Dopasowanie AI", nie na pulpicie
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $s = app(MatchStatus::class)->snapshot();

        return [
            $this->workerStat($s),
            $this->schedulerStat($s),
            $this->queueStat($s),
            $this->lastBatchStat($s),
            $this->mailsStat($s),
            $this->lastSearchStat($s),
        ];
    }

    // ** Czy worker może / właśnie pracuje, a jeśli nie — dlaczego
    protected function workerStat(array $s): Stat
    {
        [$value, $description, $color, $icon] = match (true) {
            ! $s['enabled'] => ['Wyłączony', 'Moduł wyłączony w .env (MATCH_ENABLED)', 'gray', 'heroicon-m-power'],
            ! $s['python_found'] => ['Brak Pythona', 'Nie znaleziono interpretera z MATCH_PYTHON', 'danger', 'heroicon-m-exclamation-triangle'],
            $s['running'] !== null => [
                'Pracuje',
                "Liczy {$s['running']['photos']} zdj. od ".$s['running']['started_at']->diffForHumans(),
                'success',
                'heroicon-m-arrow-path',
            ],
            $s['blocked_reason'] !== null => ['Wstrzymany', ucfirst($s['blocked_reason']), 'warning', 'heroicon-m-pause'],
            default => ['Gotowy', 'Czeka na zdjęcia w kolejce', 'success', 'heroicon-m-check-circle'],
        };

        return Stat::make('Worker', $value)
            ->description($description)
            ->descriptionIcon($icon)
            ->color($color);
    }

    // ** "Puls" — kiedy scheduler ostatnio uruchomił worker; dłuższa cisza = cron nie działa
    protected function schedulerStat(array $s): Stat
    {
        $at = $s['last_run_at'];

        $stat = Stat::make('Ostatnie uruchomienie', $at ? $at->diffForHumans() : 'nigdy');

        if (! $s['enabled']) {
            return $stat->description('Moduł wyłączony')->color('gray');
        }

        return $s['scheduler_stale']
            ? $stat->description('Scheduler nie uruchamia workera — sprawdź cron')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
            : $stat->description('Scheduler działa ('.$at?->format('H:i:s').')')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success');
    }

    protected function queueStat(array $s): Stat
    {
        $q = $s['queue'];

        $description = "Policzone: {$q['done']}";

        if ($q['failed'] > 0) {
            $description .= " · z błędem: {$q['failed']}";
        }

        if ($q['avg_ms'] > 0) {
            $description .= ' · ~'.number_format($q['avg_ms'] / 1000, 1, ',', '').' s/zdjęcie';
        }

        return Stat::make('Zdjęcia w kolejce', $q['pending'])
            ->description($description)
            ->descriptionIcon($q['failed'] > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-photo')
            ->color($q['failed'] > 0 ? 'warning' : ($q['pending'] > 0 ? 'info' : 'success'));
    }

    protected function lastBatchStat(array $s): Stat
    {
        $b = $s['last_batch'];

        if (! $b) {
            return Stat::make('Ostatnia partia', '—')->description('Brak danych');
        }

        $parts = ["Policzone {$b['processed']}"];

        if ($b['failed'] > 0) {
            $parts[] = "błędy {$b['failed']}";
        }

        if ($b['peak_rss_mb']) {
            $parts[] = 'RAM '.round($b['peak_rss_mb']).' MB';
        }

        return Stat::make('Ostatnia partia', $b['finished_at']->diffForHumans())
            ->description($b['crashed'] ? 'Proces Pythona przerwany!' : implode(' · ', $parts))
            ->descriptionIcon($b['crashed'] ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-cpu-chip')
            ->color($b['crashed'] ? 'danger' : 'gray');
    }

    // ** Zlecenia maili z kandydatami z ostatnich 7 dni
    protected function mailsStat(array $s): Stat
    {
        $r = $s['requests'];

        $labels = [
            'no_matches' => 'bez wyników',
            'queued' => 'czeka',
            'skipped' => 'pominięte',
            'expired' => 'wygasłe',
            'failed' => 'błędy',
        ];

        $parts = collect($labels)
            ->filter(fn ($label, $status) => ($r[$status] ?? 0) > 0)
            ->map(fn ($label, $status) => "{$label}: {$r[$status]}")
            ->values();

        return Stat::make('Maile z dopasowaniami (7 dni)', $r['notified'] ?? 0)
            ->description($parts->isEmpty() ? 'Wysłane maile z kandydatami' : 'wysłane · '.$parts->implode(' · '))
            ->descriptionIcon('heroicon-m-envelope')
            ->color(($r['failed'] ?? 0) > 0 ? 'danger' : 'gray');
    }

    protected function lastSearchStat(array $s): Stat
    {
        $at = $s['last_search_at'] ? Carbon::parse($s['last_search_at']) : null;

        return Stat::make('Ostatnie wyszukiwanie', $at ? $at->diffForHumans() : 'nigdy')
            ->description('Model: '.$s['model_version'])
            ->descriptionIcon('heroicon-m-magnifying-glass')
            ->color('gray');
    }
}
