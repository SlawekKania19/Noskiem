<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpeciesResource\Pages;
use App\Models\Species;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik gatunków zwierząt
class SpeciesResource extends Resource
{
    protected static ?string $model = Species::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Gatunki';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'gatunek';
    protected static ?string $pluralModelLabel = 'gatunki';
    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name_pl')->label('Nazwa (PL)')->required()->maxLength(100),
                TextInput::make('name_en')->label('Nazwa (EN)')->maxLength(100),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_pl')->label('Nazwa (PL)')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name_en')->label('Nazwa (EN)')->searchable(),
                Tables\Columns\TextColumn::make('breeds_count')
                    ->label('Ras')
                    ->counts('breeds')
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('name_pl');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSpecies::route('/'),
        ];
    }
}


