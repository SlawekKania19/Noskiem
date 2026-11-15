<?php

namespace App\Filament\Widgets\Statistics;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// ---------------------------
// Wspólne kolumny tabel województw i gatunków (wiersze z StatsReport::pick()).
// compact - węższa tabela (pół szerokości strony): bez kolumny "widziałem".
// ---------------------------
class StatsTableColumns
{
    public static function apply(Table $table, string $nameLabel, bool $compact = false): Table
    {
        $number = fn (string $name, string $label) => TextColumn::make($name)->label($label)->numeric()->alignEnd();

        return $table
            ->columns(array_values(array_filter([
                TextColumn::make('name')->label($nameLabel)->weight('medium'),
                $number('lost', 'Zaginione'),
                $number('found', 'Znalezione'),
                $number('resolved', 'Odnalezione'),
                $compact ? null : $number('sightings', 'Widziałem'),
            ])))
            ->paginated(false);
    }
}
