<?php

namespace App\Filament\Resources\ChipRegistrationResource\Pages;

use App\Filament\Resources\ChipRegistrationResource;
use App\Models\ChipRegistration;
use App\Services\ChipModerationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

// ---------------------------
// Strona szczegółów wpisu w bazie chipów.
// Akcje Zatwierdź / Odrzuć / Usuń w nagłówku strony.
// ---------------------------

class ViewChipRegistration extends ViewRecord
{
    protected static string $resource = ChipRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Zatwierdź')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Zatwierdzić wpis?')
                ->modalDescription(fn () => ChipRegistrationResource::approveDescription($this->record))
                ->visible(fn () => $this->record->mod_status === 'pending')
                ->action(function () {
                    try {
                        app(ChipModerationService::class)->approve($this->record, auth()->id());
                        Notification::make()->title('Wpis zatwierdzony')->success()->send();
                        $this->redirect(ChipRegistrationResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('reject')
                ->label('Odrzuć')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->modalHeading('Odrzuć wpis')
                ->form([
                    Textarea::make('reason')
                        ->label('Powód odrzucenia')
                        ->required()
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->visible(fn () => $this->record->mod_status === 'pending')
                ->action(function (array $data) {
                    try {
                        app(ChipModerationService::class)->reject($this->record, $data['reason'], auth()->id());
                        Notification::make()->title('Wpis odrzucony')->warning()->send();
                        $this->redirect(ChipRegistrationResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            // ** Edycja zatwierdzonego wpisu (pending_changes)
            Action::make('approveChanges')
                ->label('Zatwierdź zmiany')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Zatwierdzić zmiany?')
                ->modalDescription(function () {
                    // ** Zmiana numeru na taki, który jest już zatwierdzony w innym wpisie
                    $newChip = $this->record->pending_changes['chip_number'] ?? null;
                    $conflict = $newChip && $newChip !== $this->record->chip_number
                        && ChipRegistration::where('chip_number', $newChip)->where('mod_status', 'approved')->whereKeyNot($this->record->id)->exists();

                    return $conflict
                        ? 'Uwaga: nowy numer chipa jest już zatwierdzony w innym wpisie. Nowa wersja wpisu zastąpi obecną w wyszukiwarce.'
                        : 'Nowa wersja wpisu zastąpi obecną w wyszukiwarce.';
                })
                ->visible(fn () => $this->record->pending_changes !== null)
                ->action(function () {
                    try {
                        app(ChipModerationService::class)->approveChanges($this->record, auth()->id());
                        Notification::make()->title('Zmiany zatwierdzone')->success()->send();
                        $this->redirect(ChipRegistrationResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('rejectChanges')
                ->label('Odrzuć zmiany')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->modalHeading('Odrzuć zmiany')
                ->modalDescription('Wpis zostanie w bazie w obecnej wersji.')
                ->form([
                    Textarea::make('reason')
                        ->label('Powód odrzucenia')
                        ->required()
                        ->maxLength(500)
                        ->rows(3),
                ])
                ->visible(fn () => $this->record->pending_changes !== null)
                ->action(function (array $data) {
                    try {
                        app(ChipModerationService::class)->rejectChanges($this->record, $data['reason'], auth()->id());
                        Notification::make()->title('Zmiany odrzucone')->warning()->send();
                        $this->redirect(ChipRegistrationResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            // ** Bez maila do właściciela - usuwanie służy głównie sprzątaniu konfliktów
            DeleteAction::make()
                ->label('Usuń')
                ->modalDescription('Wpis i zdjęcie naklejki zostaną trwale usunięte.'),
        ];
    }
}
