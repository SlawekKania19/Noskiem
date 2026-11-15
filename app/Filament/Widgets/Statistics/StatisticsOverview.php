<?php

namespace App\Filament\Widgets\Statistics;

use App\Models\Animal;
use App\Services\StatsReport;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

// ---------------------------
// Kafelki na górze strony "Statystyki" (App\Filament\Pages\Statistics) - liczby
// z wybranego okresu. Tylko na tej stronie, nie na pulpicie.
// ---------------------------

class StatisticsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $report = new StatsReport($this->pageFilters['period'] ?? '365');
        $totals = $report->totals();
        $resolution = $report->resolution();

        $count = fn (string $type, string $key = 'total') => $totals[$type][$key] ?? 0;
        $rate = fn (?float $value) => $value === null ? '-' : str_replace('.', ',', (string) $value).'%';

        // ** Stan na dziś (nie zależy od okresu)
        $activeLost = Animal::where('mod_status', 'approved')->where('status', 'lost')->count();
        $activeFound = Animal::where('mod_status', 'approved')->where('status', 'found')->count();

        return [
            Stat::make('Opublikowane ogłoszenia', $count('animal_published'))
                ->description("Zaginione: {$count('animal_published', 'lost')} · Znalezione: {$count('animal_published', 'found')}")
                ->icon('heroicon-o-newspaper'),

            Stat::make('Odnalezione / zakończone', $count('animal_resolved'))
                ->description("Zaginione: {$count('animal_resolved', 'lost')} · Znalezione: {$count('animal_resolved', 'found')}")
                ->color('success')
                ->icon('heroicon-o-heart'),

            Stat::make('Skuteczność - zaginione', $rate($resolution['lost']['rate']))
                ->description("{$resolution['lost']['resolved']} z {$resolution['lost']['published']} ogłoszeń z okresu zakończonych")
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Stat::make('Czas do odnalezienia', $resolution['avg_days'] === null ? '-' : str_replace('.', ',', (string) $resolution['avg_days']).' dni')
                ->description($resolution['median_days'] === null ? 'Brak danych' : "Średnio · mediana: {$resolution['median_days']} dni")
                ->icon('heroicon-o-clock'),

            Stat::make('Zgłoszenia z formularza', $count('animal_submitted'))
                ->description("Odrzucone: {$count('animal_rejected')} · liczone od potwierdzenia e-maila")
                ->icon('heroicon-o-inbox-arrow-down'),

            Stat::make('Zgłoszenia „widziałem"', $count('sighting_submitted'))
                ->description("Wiadomości do autorów: {$count('message_sent')}")
                ->icon('heroicon-o-eye'),

            Stat::make('Rejestracje chipów', $count('chip_registered'))
                ->description("Usunięte ogłoszenia: {$count('animal_deleted')}")
                ->icon('heroicon-o-identification'),

            Stat::make('Aktywne teraz', $activeLost + $activeFound)
                ->description("Zaginione: {$activeLost} · Znalezione: {$activeFound} (stan na dziś)")
                ->color('warning')
                ->icon('heroicon-o-map-pin'),
        ];
    }
}
