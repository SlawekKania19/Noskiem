<?php

namespace App\Filament\Widgets\Statistics;

use App\Services\StatsReport;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

// ---------------------------
// Tabela "Według gatunków" na stronie "Statystyki" - te same kolumny co województwa.
// ---------------------------

class SpeciesTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return StatsTableColumns::apply(
            $table
                ->heading('Według gatunków')
                ->records(fn () => (new StatsReport($this->pageFilters['period'] ?? '365'))->bySpecies()->keyBy('name')->all())
                ->emptyStateHeading('Brak ogłoszeń w tym okresie'),
            'Gatunek',
            compact: true,
        );
    }
}
