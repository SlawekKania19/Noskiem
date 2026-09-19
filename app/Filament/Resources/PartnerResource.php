<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

// ---------------------------
// Partnerzy wspierający serwis — pozycje paska z logotypami nad stopką.
// Cała obsługa siedzi na jednej stronie (tabela + modale), bo rekord ma
// tylko kilka pól.
// ---------------------------

class PartnerResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToAdmin;

    protected static ?string $model = Partner::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'Partnerzy';
    protected static ?string $modelLabel = 'partner';
    protected static ?string $pluralModelLabel = 'partnerzy';
    protected static ?int $navigationSort = 85;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->label('Nazwa partnera')
                    ->required()
                    ->maxLength(255),

                // ** Bez logo pasek wyświetla samą nazwę — logo jest opcjonalne
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('partners')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->helperText('Najlepiej PNG lub SVG z przezroczystym tłem, poziome proporcje. Maksymalnie 2 MB. Bez logo w pasku pojawi się sama nazwa partnera.'),

                TextInput::make('url')
                    ->label('Link do strony partnera')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Opcjonalny. Bez linku baner nie będzie klikalny.')
                    ->prefixIcon('heroicon-o-link'),

                Toggle::make('is_active')
                    ->label('Aktywny')
                    ->helperText('Nieaktywny partner znika z paska, ale zostaje w bazie.')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->width(100)
                    ->extraImgAttributes(['class' => 'object-contain'])
                    ->placeholder('brak'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable(),

                // ** Link klikalny wprost z tabeli — szybciej niż wchodzenie w edycję, żeby sprawdzić dokąd prowadzi
                Tables\Columns\TextColumn::make('url')
                    ->label('Link')
                    ->url(fn (Partner $record) => $record->url)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->limit(40)
                    ->placeholder('brak'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktywny'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktywny')
                    ->placeholder('Wszyscy')
                    ->trueLabel('Tylko aktywni')
                    ->falseLabel('Tylko nieaktywni'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->modalDescription('Partner zniknie z paska na stronie, a jego logo zostanie usunięte z dysku. Tej operacji nie można cofnąć.'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Kolejność w pasku ustawiana przeciąganiem wierszy
            ->reorderable('sort')
            ->defaultSort('sort')
            ->emptyStateHeading('Brak partnerów')
            ->emptyStateDescription('Dodaj pierwszego partnera — pasek nad stopką pokaże się dopiero, gdy będzie co wyświetlić.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePartners::route('/'),
        ];
    }
}
