<?php

namespace App\Filament\Resources\SightingResource\Pages;

use App\Filament\Resources\SightingResource;
use App\Services\SightingModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

// ---------------------------
// Strona szczegółów zgłoszenia "też widziałem".
// Akcje Zatwierdź / Odrzuć w nagłówku strony.
// ---------------------------

class ViewSighting extends ViewRecord
{
    protected static string $resource = SightingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Zatwierdź')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Zatwierdzić zgłoszenie?')
                ->modalDescription('Zgłoszenie pojawi się w timeline pod ogłoszeniem.')
                ->visible(fn () => $this->record->mod_status === 'pending')
                ->action(function () {
                    try {
                        app(SightingModerationService::class)->approve($this->record, auth()->id());
                        Notification::make()->title('Zgłoszenie zatwierdzone')->success()->send();
                        $this->redirect(SightingResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('reject')
                ->label('Odrzuć')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->modalHeading('Odrzuć zgłoszenie')
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
                        app(SightingModerationService::class)->reject($this->record, $data['reason'], auth()->id());
                        Notification::make()->title('Zgłoszenie odrzucone')->warning()->send();
                        $this->redirect(SightingResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
