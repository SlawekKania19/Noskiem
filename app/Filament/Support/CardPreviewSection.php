<?php

namespace App\Filament\Support;

use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;

// ---------------------------
// Sekcja infolisty "Podgląd karty na stronie" - prawdziwa karta ogłoszenia
// (komponent <x-animal-card> ze stylami strony) w ramce, w wersji na komputer i telefon.
// Moderator widzi dokładnie to, co zobaczą odwiedzający: kadr zdjęcia, tytuł, lokalizację.
// ---------------------------
class CardPreviewSection
{
    public static function make(): Section
    {
        return Section::make('Podgląd karty na stronie')
            ->description('Tak ogłoszenie będzie wyglądać na liście. Kadr zdjęcia możesz poprawić przyciskiem „Kadry miniaturek”.')
            ->columnSpanFull()
            ->collapsible()
            ->schema([
                ViewEntry::make('card_preview')
                    ->hiddenLabel()
                    ->view('filament.card-preview-frame'),
            ]);
    }
}
