<?php

namespace App\Filament\Resources\AnimalEditResource\Pages;

use App\Filament\Actions\MatchPreviewAction;
use App\Filament\Concerns\HandlesMatchExclusions;
use App\Services\MatchPreview;
use App\Filament\Actions\PhotoFocusAction;
use App\Filament\Resources\AnimalEditResource;
use App\Services\ModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

// ---------------------------
// Strona szczegółów zgłoszenia moderacyjnego.
// Akcje Zatwierdź / Odrzuć w nagłówku strony.
// ---------------------------

class ViewAnimalEdit extends ViewRecord
{
    use HandlesMatchExclusions;

    protected static string $resource = AnimalEditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ** Podgląd maila z dopasowaniami, który autor dostanie po zatwierdzeniu
            MatchPreviewAction::make(),

            // ** Ręczna poprawka kadru miniaturek, gdy automatyczny ucina zwierzaka
            PhotoFocusAction::make(),

            Action::make('approve')
                ->label('Zatwierdź')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Zatwierdzić zgłoszenie?')
                ->modalDescription(fn () => $this->approveDescription())
                ->visible(fn () => $this->record->mod_status === 'pending')
                ->action(function () {
                    try {
                        app(ModerationService::class)->approve($this->record, auth()->id());
                        Notification::make()->title('Zgłoszenie zatwierdzone')->success()->send();
                        $this->redirect(AnimalEditResource::getUrl('index'));
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
                        app(ModerationService::class)->reject($this->record, $data['reason'], auth()->id());
                        Notification::make()->title('Zgłoszenie odrzucone')->warning()->send();
                        $this->redirect(AnimalEditResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    // ---------------------------
    // Opis okna "Zatwierdzić zgłoszenie?" - przypomnienie o propozycjach AI, bo mail z nimi
    // wychodzi zaraz po zatwierdzeniu. Wyszukiwanie dopiero przy otwarciu okna, nie przy wejściu
    // ---------------------------
    protected function approveDescription(): string
    {
        $text = 'Ogłoszenie zostanie opublikowane na stronie.';
        $shown = app(MatchPreview::class)->shownCount($this->record);

        if ($shown > 0) {
            $text .= " Podobne ogłoszenia znalezione przez AI: {$shown} - autor dostanie je w mailu zaraz po zatwierdzeniu."
                .' Jeśli któraś propozycja nie pasuje, zamknij to okno i odrzuć ją w „Podobne (AI)”.';
        }

        return $text;
    }
}
