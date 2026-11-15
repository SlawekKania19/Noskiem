<?php

namespace App\Filament\Widgets\Statistics;

use App\Services\StatsReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

// ---------------------------
// Najczęściej czytane wpisy bloga w wybranym okresie (App\Services\PostViewCounter).
// W nagłówku suma odsłon z okresu; kolumna "Łącznie" - od początku liczenia.
// ---------------------------

class BlogTopPostsTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $blog = (new StatsReport($this->pageFilters['period'] ?? '365'))->blog();

        return $table
            ->heading('Blog - najczęściej czytane')
            ->description('Odsłony w okresie: '.number_format($blog['total'], 0, ',', ' ').' (bez botów, zalogowanych i odświeżeń)')
            ->records(fn () => $blog['top']->map(fn ($post) => (array) $post)->keyBy('id')->all())
            ->columns([
                TextColumn::make('title')
                    ->label('Wpis')
                    ->limit(50)
                    ->url(fn (array $record) => route('blog.show', $record['slug']), shouldOpenInNewTab: true),
                TextColumn::make('period_views')->label('W okresie')->numeric()->alignEnd(),
                TextColumn::make('views_count')->label('Łącznie')->numeric()->alignEnd(),
            ])
            ->emptyStateHeading('Brak odsłon w tym okresie')
            ->paginated(false);
    }
}
