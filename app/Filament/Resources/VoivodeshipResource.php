<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoivodeshipResource\Pages;
use App\Models\Voivodeship;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik województw
class VoivodeshipResource extends Resource
{
    protected static ?string $model = Voivodeship::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Województwa';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'województwo';
    protected static ?string $pluralModelLabel = 'województwa';
    protected static ?int $navigationSort = 10;

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
                Tables\Columns\TextColumn::make('cities_count')
                    ->label('Miast')
                    ->counts('cities')
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
            'index' => Pages\ManageVoivodeships::route('/'),
        ];
    }
}


