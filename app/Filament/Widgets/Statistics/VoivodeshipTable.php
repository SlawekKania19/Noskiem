<?php

namespace App\Filament\Widgets\Statistics;

use App\Services\StatsReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

// ---------------------------
// Tabela "Według województw" na stronie "Statystyki" - ogłoszenia opublikowane
// w okresie (zaginione/znalezione), odnalezione i zgłoszenia "widziałem".
// Dane spoza Eloquenta (agregaty z StatsReport) - przez Table::records().
// ---------------------------

class VoivodeshipTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return StatsTableColumns::apply(
            $table
                ->heading('Według województw')
                ->records(fn () => (new StatsReport($this->pageFilters['period'] ?? '365'))->byVoivodeship()->keyBy('name')->all()),
            'Województwo',
        );
    }
}
