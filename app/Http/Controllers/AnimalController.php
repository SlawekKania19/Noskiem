<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\City;
use App\Models\Color;
use App\Models\Species;
use App\Models\Voivodeship;
use Illuminate\Http\Request;

// ---------------------------
// Kontroler dla zarządzania ogłoszeniami zwierząt. Zawiera metody do wyświetlania listy ogłoszeń, wyświetlania szczegółów ogłoszenia oraz usuwania ogłoszenia.
// ---------------------------

class AnimalController extends Controller
{
    // Wyświetla listę zatwierdzonych ogłoszeń z obsługą filtrów (gatunek, rasa, województwo, miasto, status, kolor). Renderuje widok publiczny (animals.index).
    public function index(Request $request)
    {
        $animals = Animal::where('mod_status', 'approved')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->when($request->filled('species_id'), fn ($q) => $q->where('species_id', $request->species_id))
            ->when($request->filled('breed_id'), fn ($q) => $q->where('breed_id', $request->breed_id))
            ->when($request->filled('voivodeship_id'), fn ($q) => $q->where('voivodeship_id', $request->voivodeship_id))
            ->when($request->filled('city_id'), fn ($q) => $q->where('city_id', $request->city_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('color_id'), fn ($q) => $q->whereHas(
                'colors',
                fn ($q2) => $q2->where('colors.id', $request->color_id)
            ))
            ->orderBy('created_at', 'desc')
            ->get();

        // ** Listy słownikowe do formularza filtrów
        return view('animals.index', [
            'animals' => $animals,
            'species' => Species::orderBy('sortkey')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'cities' => City::orderBy('name_pl')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
        ]);
    }

    // Zwraca listę zatwierdzonych ogłoszeń w formacie JSON — używane przez GET /api/animals.
    public function indexJson()
    {
        return Animal::where('mod_status', 'approved')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Wyświetla szczegóły ogłoszenia (galeria, opis, kontakt, formularz wiadomości). Renderuje widok publiczny (animals.show).
    public function show(Animal $animal)
    {
        $animal->load(['species', 'breed', 'voivodeship', 'city', 'photos']);

        return view('animals.show', compact('animal'));
    }

    // Zwraca szczegóły ogłoszenia w formacie JSON — używane przez GET /api/animals/{animal}.
    public function showJson(Animal $animal)
    {
        return $animal->load([
            'species',
            'breed',
            'voivodeship',
            'city',
            'photos',
        ]);
    }

    // Usuwa konkretne ogłoszenie zwierzęcia wraz ze wszystkimi powiązanymi zdjęciami. Zwraca odpowiedź w formacie JSON z komunikatem o powodzeniu operacji.
    public function destroy(Animal $animal)
    {
        foreach ($animal->photos as $photo) {
            \Storage::disk('public')->delete($photo->path);
        }

        $animal->delete();

        return response()->json([
            'message' => 'Ogłoszenie zostało usunięte.',
        ]);
    }
}
