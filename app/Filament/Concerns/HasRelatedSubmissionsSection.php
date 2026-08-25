<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\AnimalEditResource;
use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Models\AnimalEdit;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

// ---------------------------
// Sekcja infolisty pokazująca inne zgłoszenia (Animal + AnimalEdit) z tego samego
// adresu e-mail — współdzielona przez AnimalResource i AnimalEditResource, żeby
// moderator widział próby "podbicia" pozycji zarówno przy nowych zgłoszeniach,
// jak i przy przeglądaniu już zatwierdzonych ogłoszeń.
// ---------------------------

trait HasRelatedSubmissionsSection
{
    protected static function relatedSubmissionsSection(): Section
    {
        return Section::make('Inne zgłoszenia z tego adresu e-mail')
            ->description('Ostatnie 5 zgłoszeń powiązanych z tym samym adresem e-mail.')
            ->columnSpanFull()
            ->schema([
                RepeatableEntry::make('related_by_contact_email')
                    // Bez ->hiddenLabel() pusty label('') traktowany jest jako "brak etykiety"
                    // i Filament sam generuje ją z nazwy pola (efekt: widoczny angielski tekst)
                    ->label('Inne zgłoszenia z tego adresu e-mail')
                    ->hiddenLabel()
                    ->state(fn ($record) => $record->relatedByContactEmail())
                    ->table([
                        TableColumn::make('Tytuł'),
                        TableColumn::make('Typ'),
                        TableColumn::make('Moderacja'),
                        TableColumn::make('Dodano'),
                    ])
                    ->schema([
                        TextEntry::make('title')
                            ->label('Tytuł')
                            ->html()
                            // Nowe zgłoszenia z niepotwierdzonym e-mailem są ukryte przed moderatorem
                            // (patrz AnimalEditResource::getEloquentQuery) — link prowadziłby do 404,
                            // więc zamiast linku pokazujemy informację pod tytułem
                            ->formatStateUsing(fn ($state, $record) => static::isRecordHiddenFromModerator($record)
                                ? e($state) . '<br><span class="fi-in-text text-xs text-gray-500 dark:text-gray-400">E-mail niepotwierdzony</span>'
                                : e($state))
                            ->url(fn ($record) => match (true) {
                                $record instanceof Animal => AnimalResource::getUrl('view', ['record' => $record]),
                                static::isRecordHiddenFromModerator($record) => null,
                                default => AnimalEditResource::getUrl('view', ['record' => $record]),
                            })
                            ->openUrlInNewTab(),

                        TextEntry::make('status')
                            ->label('Typ')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'lost'  => 'danger',
                                'found' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'lost'  => 'Zaginął',
                                'found' => 'Znaleziony',
                                default => $state,
                            }),

                        TextEntry::make('mod_status')
                            ->label('Moderacja')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'resolved' => 'info',
                                default    => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'pending'  => 'Oczekuje',
                                'approved' => 'Zatwierdzone',
                                'rejected' => 'Odrzucone',
                                'resolved' => 'Rozwiązane',
                                default    => $state,
                            }),

                        TextEntry::make('created_at')
                            ->label('Dodano')
                            ->dateTime('d.m.Y H:i'),
                    ]),
            ])
            ->visible(fn ($record) => $record->relatedByContactEmail()->isNotEmpty());
    }

    // Nowe zgłoszenie z niepotwierdzonym e-mailem — niewidoczne dla moderatora
    // (patrz AnimalEditResource::getEloquentQuery)
    protected static function isRecordHiddenFromModerator($record): bool
    {
        return $record instanceof AnimalEdit
            && $record->animal_id === null
            && $record->email_verified_at === null;
    }
}
