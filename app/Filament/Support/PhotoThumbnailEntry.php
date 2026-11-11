<?php

namespace App\Filament\Support;

use App\Models\Photo;
use Filament\Infolists\Components\ImageEntry;

// ---------------------------
// Miniaturka zdjęcia w podglądzie panelu (moderacja, ogłoszenia, zgłoszenia "widziałem")
// wyglądająca dokładnie jak na stronie: kwadrat, object-fit: cover, kadr wokół zwierzaka
// (Photo::objectPosition - ten sam co na karcie ogłoszenia). Kliknięcie otwiera pełne
// zdjęcie w nowej karcie - moderator musi móc zobaczyć też to, co miniaturka ucina.
// Używać wewnątrz RepeatableEntry::make('photos') - $record to wtedy Photo.
// ---------------------------
class PhotoThumbnailEntry
{
    public const SIZE = 160;

    public static function make(?callable $extraStyle = null): ImageEntry
    {
        return ImageEntry::make('path')
            ->hiddenLabel()
            ->disk('public')
            ->imageWidth(self::SIZE)
            ->imageHeight(self::SIZE)
            ->url(fn (Photo $record) => asset('storage/'.$record->path))
            ->openUrlInNewTab()
            ->tooltip('Kliknij, żeby zobaczyć całe zdjęcie')
            ->extraImgAttributes(fn (Photo $record) => [
                'style' => 'object-fit: cover; border-radius: 0.75rem; object-position: '
                    .$record->objectPosition().';'
                    .($extraStyle ? ' '.$extraStyle($record) : ''),
            ]);
    }
}
