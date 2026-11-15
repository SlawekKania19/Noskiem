<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik miast - powiązane z województwem
class CityResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToAdmin;

    protected static ?string $model = City::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Miasta';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'miasto';
    protected static ?string $pluralModelLabel = 'miasta';
    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('voivodeship_id')
                    ->label('Województwo')
                    ->relationship('voivodeship', 'name_pl')
                    ->required()
                    ->searchable(),
                TextInput::make('name_pl')->label('Nazwa (PL)')->required()->maxLength(150),
                TextInput::make('name_en')->label('Nazwa (EN)')->maxLength(150),
                // ** Rozróżniają miejscowości o tej samej nazwie w województwie (np. kilka "Dąbrowa")
                TextInput::make('kind')->label('Rodzaj')->placeholder('np. wieś, miasto')->maxLength(30),
                TextInput::make('gmina')->label('Gmina')->maxLength(255),
                TextInput::make('powiat')->label('Powiat')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_pl')->label('Nazwa (PL)')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name_en')->label('Nazwa (EN)')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kind')->label('Rodzaj')->toggleable(),
                Tables\Columns\TextColumn::make('gmina')->label('Gmina')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('powiat')->label('Powiat')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('voivodeship.name_pl')->label('Województwo')->sortable(),
                Tables\Columns\IconColumn::make('is_ambiguous')->label('Nazwa powtarza się')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('voivodeship_id')
                    ->label('Województwo')
                    ->relationship('voivodeship', 'name_pl'),
                Tables\Filters\TernaryFilter::make('is_ambiguous')->label('Nazwa powtarza się w województwie'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('name_pl')
            ->defaultPaginationPageOption(50);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCities::route('/'),
        ];
    }
}


