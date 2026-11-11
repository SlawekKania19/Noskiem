<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalEdit;
use Illuminate\Http\Request;

// ---------------------------
// Podgląd karty ogłoszenia dokładnie tak, jak na stronie głównej - prawdziwy komponent
// <x-animal-card> ze stylami strony (Vite). Osadzany w panelu Filament w ramce (iframe),
// bo panel nie ma klas Tailwinda frontu. Tylko admin i moderator.
// - animal - opublikowane ogłoszenie
// - edit   - zgłoszenie przed moderacją: budujemy tymczasowe Animal (bez zapisu),
//            żeby karta liczyła tytuł, lokalizację i zdjęcie główne jak po zatwierdzeniu
// Parametry (okno "Kadry miniaturek"):
// - ?photo=ID - karta z tym zdjęciem zamiast głównego (tylko w pamięci, nic nie zapisujemy)
// - ?embed=1  - sama karta desktopowa + nasłuch postMessage z nowym kadrem (podgląd na żywo)
// ---------------------------
class CardPreviewController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        $user = auth()->user();
        abort_unless($user && ($user->is_admin || $user->is_moderator), 403);

        $animal = match ($type) {
            'animal' => Animal::with(['photos', 'breed', 'city', 'species', 'voivodeship'])->findOrFail($id),
            'edit'   => $this->fromEdit(AnimalEdit::with(['photos', 'breed', 'city', 'species', 'voivodeship'])->findOrFail($id)),
            default  => abort(404),
        };

        // ** Zdjęcia "widziałem" nie trafiają na kartę ogłoszenia
        $animal->setRelation('photos', $animal->photos->whereNull('sighting_id')->values());

        // ** Wybrane zdjęcie jako główne - tylko na potrzeby podglądu, bez zapisu
        $photoId = $request->integer('photo');

        // ** Zwykła pętla, nie each() - each() przerywa się, gdy funkcja zwróci false
        if ($photoId && $animal->photos->contains('id', $photoId)) {
            foreach ($animal->photos as $photo) {
                $photo->is_main = $photo->id === $photoId;
            }
        }

        return view('admin.card-preview', [
            'animal' => $animal,
            'embed' => $request->boolean('embed'),
        ]);
    }

    private function fromEdit(AnimalEdit $edit): Animal
    {
        $animal = new Animal($edit->only([
            'status', 'animal_name', 'location_text', 'species_id', 'breed_id', 'city_id', 'voivodeship_id',
        ]));

        // ** id tylko do wygenerowania linku w karcie (w podglądzie i tak nieklikalnego)
        $animal->id = $edit->animal_id ?? 0;

        foreach (['photos', 'breed', 'city', 'species', 'voivodeship'] as $relation) {
            $animal->setRelation($relation, $edit->{$relation});
        }

        return $animal;
    }
}
