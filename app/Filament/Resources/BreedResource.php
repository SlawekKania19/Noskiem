<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedResource\Pages;
use App\Models\Breed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik ras — powiązane z gatunkiem
class BreedResource extends Resource
{
    protected static ?string $model = Breed::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Rasy';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'rasa';
    protected static ?string $pluralModelLabel = 'rasy';
    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('species_id')
                    ->label('Gatunek')
                    ->relationship('species', 'name_pl')
                    ->required()
                    ->searchable(),
                TextInput::make('breed_pl')->label('Nazwa (PL)')->required()->maxLength(100),
                TextInput::make('breed_en')->label('Nazwa (EN)')->maxLength(100),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('breed_pl')->label('Nazwa (PL)')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('breed_en')->label('Nazwa (EN)')->searchable(),
                Tables\Columns\TextColumn::make('species.name_pl')->label('Gatunek')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('species_id')
                    ->label('Gatunek')
                    ->relationship('species', 'name_pl'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('breed_pl');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBreeds::route('/'),
        ];
    }
}


