<?php

namespace App\Filament\Actions;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Services\MatchPreview;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;

// ---------------------------
// Przycisk "Podobne (AI)" na podglądzie zgłoszenia (moderacja) i ogłoszenia.
// Otwiera panel boczny z wynikami dopasowania po zdjęciach na żywo oraz historią
// maili do autora (App\Services\MatchPreview). Tylko admin i moderator.
// ---------------------------

class MatchPreviewAction
{
    public static function make(): Action
    {
        return Action::make('matchPreview')
            ->label('Podobne (AI)')
            ->icon('heroicon-o-sparkles')
            ->color('gray')
            ->visible(fn () => config('noskiem_match.enabled')
                && (auth()->user()?->is_admin || auth()->user()?->is_moderator))
            ->slideOver()
            ->modalWidth(Width::FourExtraLarge)
            ->modalHeading('Podobne ogłoszenia (AI)')
            // ** Wyszukiwanie uruchamia się dopiero przy otwarciu panelu, nie przy wejściu na stronę
            ->modalContent(fn (Animal|AnimalEdit $record) => view('filament.match-preview', [
                'data' => app(MatchPreview::class)->build($record),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Zamknij');
    }
}
