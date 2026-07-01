<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// ---------------------------
// Resource wiadomości kontaktowych (read-only).
// Wiadomości są wysyłane przez użytkowników przez formularz na stronie.
// ---------------------------

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Wiadomości';
    protected static ?string $modelLabel = 'wiadomość';
    protected static ?string $pluralModelLabel = 'wiadomości';
    protected static ?int $navigationSort = 3;

    // Brak formularza — wiadomości są tylko do odczytu
    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ---------------------------
    // Tabela — lista wiadomości
    // ---------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nadawca')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Treść')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->message),

                // Tytuł ogłoszenia powiązanego z wiadomością
                Tables\Columns\TextColumn::make('animal.title')
                    ->label('Ogłoszenie')
                    ->limit(40)
                    ->url(fn ($record) => $record->animal_id
                        ? AnimalResource::getUrl('view', ['record' => $record->animal_id])
                        : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Wysłano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filtr po gatunku powiązanego ogłoszenia
                Tables\Filters\SelectFilter::make('animal.species_id')
                    ->label('Gatunek ogłoszenia')
                    ->relationship('animal.species', 'name_pl'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ---------------------------
    // Infolist — pełna treść wiadomości
    // ---------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Nadawca')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('name')->label('Imię i nazwisko'),
                    TextEntry::make('email')->label('E-mail')->copyable(),
                    TextEntry::make('created_at')->label('Data wysłania')->dateTime('d.m.Y H:i'),
                ]),
            ]),

            Section::make('Treść wiadomości')->schema([
                TextEntry::make('message')
                    ->label('')
                    ->prose(),
            ]),

            Section::make('Powiązane ogłoszenie')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('animal.title')->label('Tytuł ogłoszenia'),
                    TextEntry::make('animal.contact_email')->label('E-mail właściciela')->copyable(),
                ]),
            ])->visible(fn ($record) => $record?->animal_id !== null),

        ]);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'view'  => Pages\ViewMessage::route('/{record}'),
        ];
    }
}


