<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Statistics\BlogTopPostsTable;
use App\Filament\Widgets\Statistics\MonthlyListingsChart;
use App\Filament\Widgets\Statistics\SpeciesTable;
use App\Filament\Widgets\Statistics\StatisticsOverview;
use App\Filament\Widgets\Statistics\VoivodeshipTable;
use App\Services\StatsReport;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

// ---------------------------
// Strona "Statystyki" - podstawowe statystyki prowadzone przez samą aplikację
// (niezależnie od Google Analytics): zgłoszenia, odnalezienia, województwa, gatunki,
// odsłony bloga. Dane z dziennika stat_events (App\Support\Stats) i post_views.
// Drugi "dashboard" Filamenta pod własnym adresem - daje wspólny filtr okresu
// (pageFilters) dla wszystkich widgetów na stronie.
// ---------------------------

class Statistics extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'statystyki';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Statystyki';
    protected static ?string $title = 'Statystyki';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->is_admin || $user?->is_moderator);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Okres')
                ->options(StatsReport::PERIODS)
                ->default('365')
                ->selectablePlaceholder(false),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            StatisticsOverview::class,
            MonthlyListingsChart::class,
            VoivodeshipTable::class,
            SpeciesTable::class,
            BlogTopPostsTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}
