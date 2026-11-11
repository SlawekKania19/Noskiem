<?php

namespace App\Filament\Actions;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;

// ---------------------------
// Przycisk "Kadry miniaturek" na podglądzie zgłoszenia (moderacja) i ogłoszenia.
// Dla każdego zdjęcia moderator może kliknąć miejsce, które ma być środkiem miniaturki
// (np. głowa zwierzaka) - z podglądem kwadratowej miniaturki na żywo. Zapisuje tylko
// photos.focus_manual - plik zdjęcia i kadr z detekcji (photos.focus) zostają bez zmian,
// więc "Przywróć automatyczny" zawsze ma do czego wrócić.
// ---------------------------

class PhotoFocusAction
{
    public static function make(): Action
    {
        return Action::make('photoFocus')
            ->label('Kadry miniaturek')
            ->icon('heroicon-o-viewfinder-circle')
            ->color('gray')
            ->visible(fn (Animal|AnimalEdit $record) => self::photos($record)->isNotEmpty())
            ->modalHeading('Kadry miniaturek')
            ->modalDescription('Kliknij na zdjęciu miejsce, które ma być środkiem miniaturki - najlepiej pyszczek zwierzaka. Obok widać podgląd miniaturki.')
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel('Zapisz kadry')
            ->fillForm(fn (Animal|AnimalEdit $record) => [
                'focus' => self::photos($record)
                    ->mapWithKeys(fn (Photo $photo) => ['p'.$photo->id => $photo->focus_manual])
                    ->all(),
            ])
            ->schema(fn (Animal|AnimalEdit $record) => self::photos($record)
                ->map(fn (Photo $photo) => ViewField::make('focus.p'.$photo->id)
                    ->hiddenLabel()
                    ->view('filament.photo-focus-field')
                    ->viewData([
                        'url' => asset('storage/'.$photo->path),
                        // ** Kadr bez ręcznej poprawki - do podglądu "automatyczny"
                        'autoPosition' => (clone $photo)->forceFill(['focus_manual' => null])->objectPosition(),
                        'detected' => $photo->focus_source,
                    ]))
                ->all())
            ->action(function (array $data, Animal|AnimalEdit $record) {
                $changed = 0;

                foreach (self::photos($record) as $photo) {
                    $point = $data['focus']['p'.$photo->id] ?? null;
                    $valid = is_array($point) && isset($point[0], $point[1]);

                    $photo->setManualFocus($valid ? (float) $point[0] : null, $valid ? (float) $point[1] : null);

                    if ($photo->isDirty(['focus_manual', 'width', 'height'])) {
                        $photo->save();
                        $changed++;
                    }
                }

                Notification::make()
                    ->title($changed ? "Zapisano kadry ({$changed})" : 'Bez zmian')
                    ->success()
                    ->send();
            });
    }

    // ** Zdjęcia samego ogłoszenia (bez zdjęć ze zgłoszeń "widziałem")
    private static function photos(Animal|AnimalEdit $record)
    {
        return $record->photos()->whereNull('sighting_id')->orderByDesc('is_main')->orderBy('id')->get();
    }
}
