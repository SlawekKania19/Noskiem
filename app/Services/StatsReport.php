<?php

namespace App\Services;

use App\Models\Species;
use App\Models\Voivodeship;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Zestawienia do strony "Statystyki" w panelu (App\Filament\Pages\Statistics).
// Źródło: dziennik stat_events (przetrwa usunięcie ogłoszeń) oraz post_views (blog).
// Okres: liczba dni wstecz albo null = cała historia.
// ---------------------------
class StatsReport
{
    // ** Okresy do wyboru w panelu
    public const PERIODS = [
        '30'  => 'Ostatnie 30 dni',
        '90'  => 'Ostatnie 90 dni',
        '365' => 'Ostatnie 12 miesięcy',
        'all' => 'Cała historia',
    ];

    private ?Carbon $from;

    public function __construct(?string $period = 'all')
    {
        $this->from = is_numeric($period) ? now()->subDays((int) $period)->startOfDay() : null;
    }

    // ** Zdarzenia danego typu w okresie
    private function events(string|array $types): Builder
    {
        return DB::table('stat_events')
            ->whereIn('type', (array) $types)
            ->when($this->from, fn ($q) => $q->where('occurred_at', '>=', $this->from));
    }

    // ---------------------------
    // Liczba zdarzeń każdego typu w podziale na zaginione/znalezione:
    // ['animal_published' => ['lost' => 10, 'found' => 4, 'total' => 14], ...]
    // ---------------------------
    public function totals(): array
    {
        $rows = DB::table('stat_events')
            ->when($this->from, fn ($q) => $q->where('occurred_at', '>=', $this->from))
            ->select('type', 'listing_status', DB::raw('COUNT(*) as total'))
            ->groupBy('type', 'listing_status')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $totals[$row->type] ??= ['lost' => 0, 'found' => 0, 'total' => 0];
            $totals[$row->type]['total'] += $row->total;

            if (in_array($row->listing_status, ['lost', 'found'], true)) {
                $totals[$row->type][$row->listing_status] += $row->total;
            }
        }

        return $totals;
    }

    // ---------------------------
    // Skuteczność: z ogłoszeń opublikowanych w okresie - ile zostało zakończonych
    // jako odnalezione (po subject_id, więc liczy się też odnalezienie po końcu okresu).
    // Do tego średni i medianowy czas do odnalezienia (zdarzenia z okresu).
    // ---------------------------
    public function resolution(): array
    {
        $result = [];

        foreach (['lost', 'found'] as $status) {
            $published = $this->events('animal_published')->where('listing_status', $status);

            $resolved = DB::table('stat_events')
                ->where('type', 'animal_resolved')
                ->whereIn('subject_id', (clone $published)->select('subject_id'))
                ->distinct()
                ->count('subject_id');

            $publishedCount = (clone $published)->count();

            $result[$status] = [
                'published' => $publishedCount,
                'resolved'  => $resolved,
                'rate'      => $publishedCount > 0 ? round($resolved / $publishedCount * 100, 1) : null,
            ];
        }

        $days = $this->events('animal_resolved')->whereNotNull('days')->orderBy('days')->pluck('days');

        $result['avg_days'] = $days->isNotEmpty() ? round($days->avg(), 1) : null;
        $result['median_days'] = $days->isNotEmpty() ? $days->median() : null;

        return $result;
    }

    // ---------------------------
    // Tabela per województwo: opublikowane zaginione/znalezione, odnalezione, "widziałem".
    // Wszystkie 16 województw - także z zerami (brak ogłoszeń to też informacja).
    // ---------------------------
    public function byVoivodeship(): Collection
    {
        $counts = $this->countsBy('voivodeship_id');

        return Voivodeship::orderBy('name_pl')->get(['id', 'name_pl'])
            ->map(fn ($v) => ['name' => $v->name_pl, ...$this->pick($counts, $v->id)])
            ->push(['name' => 'Bez województwa', ...$this->pick($counts, null)])
            ->filter(fn ($row) => $row['name'] !== 'Bez województwa' || $row['all'] > 0)
            ->values();
    }

    // ** Tabela per gatunek - tylko gatunki, które wystąpiły
    public function bySpecies(): Collection
    {
        $counts = $this->countsBy('species_id');
        $names = Species::pluck('name_pl', 'id');

        return collect(array_keys($counts))
            ->map(fn ($id) => ['name' => $names[$id] ?? 'Inny / nieznany', ...$this->pick($counts, $id === '' ? null : $id)])
            ->sortByDesc('all')
            ->values();
    }

    // ** Zliczenia [klucz][typ|status] dla tabel województw/gatunków
    private function countsBy(string $column): array
    {
        $rows = $this->events(['animal_published', 'animal_resolved', 'sighting_submitted'])
            ->select($column, 'type', 'listing_status', DB::raw('COUNT(*) as total'))
            ->groupBy($column, 'type', 'listing_status')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $key = (string) ($row->$column ?? '');
            $bucket = match ($row->type) {
                'animal_published'   => $row->listing_status === 'found' ? 'found' : 'lost',
                'animal_resolved'    => 'resolved',
                'sighting_submitted' => 'sightings',
            };

            $counts[$key][$bucket] = ($counts[$key][$bucket] ?? 0) + $row->total;
        }

        return $counts;
    }

    private function pick(array $counts, $id): array
    {
        $row = $counts[(string) ($id ?? '')] ?? [];
        $lost = $row['lost'] ?? 0;
        $found = $row['found'] ?? 0;

        return [
            'lost'      => $lost,
            'found'     => $found,
            'all'       => $lost + $found,
            'resolved'  => $row['resolved'] ?? 0,
            'sightings' => $row['sightings'] ?? 0,
        ];
    }

    // ---------------------------
    // Miesięczny przebieg (ostatnie N miesięcy) dla wykresu - niezależny od wybranego okresu.
    // Grupowanie w PHP, żeby nie zależeć od funkcji dat konkretnej bazy (MySQL / SQLite w testach).
    // ---------------------------
    public static function monthly(array $types, int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[$start->copy()->addMonths($i)->format('Y-m')] = 0;
        }

        $series = array_fill_keys($types, $labels);

        DB::table('stat_events')
            ->whereIn('type', $types)
            ->where('occurred_at', '>=', $start)
            ->orderBy('id')
            ->select('type', 'occurred_at')
            ->lazy(2000)
            ->each(function ($event) use (&$series) {
                $month = substr((string) $event->occurred_at, 0, 7);

                if (isset($series[$event->type][$month])) {
                    $series[$event->type][$month]++;
                }
            });

        return ['labels' => array_keys($labels), 'series' => $series];
    }

    // ---------------------------
    // Blog: odsłony w okresie i najczęściej czytane wpisy
    // ---------------------------
    public function blog(int $limit = 10): array
    {
        $views = DB::table('post_views')->when($this->from, fn ($q) => $q->where('date', '>=', $this->from->toDateString()));

        $top = (clone $views)
            ->join('posts', 'posts.id', '=', 'post_views.post_id')
            ->select('posts.id', 'posts.title', 'posts.slug', 'posts.views_count', DB::raw('SUM(post_views.views) as period_views'))
            ->groupBy('posts.id', 'posts.title', 'posts.slug', 'posts.views_count')
            ->orderByDesc('period_views')
            ->limit($limit)
            ->get();

        return [
            'total' => (int) (clone $views)->sum('views'),
            'top'   => $top,
        ];
    }
}
