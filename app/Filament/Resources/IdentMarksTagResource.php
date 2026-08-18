<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdentMarksTagResource\Pages;
use App\Models\IdentMarksTag;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// Słownik podpowiedzi "znaków szczególnych" (przyciski szybkiego dodawania w formularzu ogłoszenia)
class IdentMarksTagResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToAdmin;

    protected static ?string $model = IdentMarksTag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Znaki szczególne';
    protected static string|\UnitEnum|null $navigationGroup = 'Słowniki';
    protected static ?string $modelLabel = 'znak szczególny';
    protected static ?string $pluralModelLabel = 'znaki szczególne';
    protected static ?int $navigationSort = 16;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->label('Treść podpowiedzi')->required()->maxLength(100),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Znak szczególny')->searchable()->sortable(),
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
            'index' => Pages\ManageIdentMarksTags::route('/'),
        ];
    }
}
