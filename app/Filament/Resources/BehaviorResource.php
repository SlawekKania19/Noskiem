<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BehaviorResource\Pages;
use App\Models\Behavior;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik zachowań (przyciski szybkiego dodawania w formularzu ogłoszenia)
class BehaviorResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToAdmin;

    protected static ?string $model = Behavior::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-face-smile';
    protected static ?string $navigationLabel = 'Zachowania';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'zachowanie';
    protected static ?string $pluralModelLabel = 'zachowania';
    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->label('Nazwa zachowania')->required()->maxLength(50),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Zachowanie')->searchable()->sortable(),
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
            'index' => Pages\ManageBehaviors::route('/'),
        ];
    }
}
