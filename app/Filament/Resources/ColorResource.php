<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ColorResource\Pages;
use App\Models\Color;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik kolorów (wielokrotny wybór przy ogłoszeniu)
class ColorResource extends Resource
{
    protected static ?string $model = Color::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Kolory';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'kolor';
    protected static ?string $pluralModelLabel = 'kolory';
    protected static ?int $navigationSort = 14;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->label('Nazwa koloru')->required()->maxLength(50),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Kolor')->searchable()->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageColors::route('/'),
        ];
    }
}


