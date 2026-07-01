<?php

namespace App\Filament\Resources\AnimalResource\RelationManagers;

use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Relation manager zdjęć ogłoszenia.
// Podgląd, ustawianie głównego i usuwanie (razem z plikiem).
// ---------------------------

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Zdjęcia';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Podgląd miniaturki
                Tables\Columns\ImageColumn::make('path')
                    ->label('Zdjęcie')
                    ->disk('public')
                    ->width(100)
                    ->height(75),

                Tables\Columns\IconColumn::make('is_main')
                    ->label('Główne')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('path')
                    ->label('Ścieżka pliku')
                    ->limit(50),
            ])
            ->actions([
                // Ustaw jako główne — zeruje is_main na pozostałych zdjęciach
                Actions\Action::make('set_main')
                    ->label('Ustaw jako główne')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn ($record) => ! $record->is_main)
                    ->requiresConfirmation()
                    ->modalHeading('Ustawić jako główne zdjęcie?')
                    ->action(function ($record) {
                        $record->animal->photos()->update(['is_main' => false]);
                        $record->update(['is_main' => true]);
                        Notification::make()->title('Ustawiono jako zdjęcie główne')->success()->send();
                    }),

                // Usuwanie — kasuje rekord i plik z dysku
                Actions\DeleteAction::make()
                    ->using(function ($record) {
                        Storage::disk('public')->delete($record->path);
                        $record->delete();
                    })
                    ->after(function () {
                        Notification::make()->title('Zdjęcie usunięte')->success()->send();
                    }),
            ])
            ->headerActions([])
            ->emptyStateHeading('Brak zdjęć')
            ->emptyStateDescription('Zdjęcia są dodawane przez formularz zgłoszenia.');
    }
}


