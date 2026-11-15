<?php

namespace App\Filament\Widgets\Statistics;

use App\Services\StatsReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Wykres miesięczny (ostatnie 12 miesięcy) na stronie "Statystyki":
// opublikowane zaginione / znalezione i odnalezione. Zawsze 12 miesięcy -
// filtr okresu strony dotyczy kafelków i tabel, a trend ma sens w dłuższym oknie.
// ---------------------------

class MonthlyListingsChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Ostatnie 12 miesięcy';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // ** Zaginione i znalezione rozdzielamy osobnym zapytaniem po listing_status
        $published = $this->monthlyByStatus();
        $resolved = StatsReport::monthly(['animal_resolved']);

        return [
            'datasets' => [
                ['label' => 'Zaginione - opublikowane', 'data' => array_values($published['lost']), 'borderColor' => '#994d0a', 'backgroundColor' => '#994d0a'],
                ['label' => 'Znalezione - opublikowane', 'data' => array_values($published['found']), 'borderColor' => '#606c38', 'backgroundColor' => '#606c38'],
                ['label' => 'Odnalezione / zakończone', 'data' => array_values($resolved['series']['animal_resolved']), 'borderColor' => '#0284c7', 'backgroundColor' => '#0284c7'],
            ],
            'labels' => array_map(
                fn ($month) => Carbon::createFromFormat('Y-m', $month)->locale('pl')->translatedFormat('M Y'),
                $resolved['labels'],
            ),
        ];
    }

    private function monthlyByStatus(): array
    {
        $empty = array_fill_keys(StatsReport::monthly([])['labels'], 0);
        $result = ['lost' => $empty, 'found' => $empty];

        DB::table('stat_events')
            ->where('type', 'animal_published')
            ->whereIn('listing_status', ['lost', 'found'])
            ->where('occurred_at', '>=', now()->startOfMonth()->subMonths(11))
            ->orderBy('id')
            ->select('listing_status', 'occurred_at')
            ->lazy(2000)
            ->each(function ($event) use (&$result) {
                $month = substr((string) $event->occurred_at, 0, 7);

                if (isset($result[$event->listing_status][$month])) {
                    $result[$event->listing_status][$month]++;
                }
            });

        return $result;
    }
}
