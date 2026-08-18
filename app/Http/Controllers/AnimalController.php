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
    // Wyświetla listę zatwierdzonych ogłoszeń z obsługą filtrów (gatunek, rasa, województwo, miasto, status, kolor) oraz wyszukiwarki tekstowej (q). Renderuje widok publiczny (animals.index).
    public function index(Request $request)
    {
        $searchTerm = $request->filled('q') ? $this->toFulltextBooleanQuery((string) $request->input('q')) : '';

        $animals = Animal::where('mod_status', 'approved')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->when($searchTerm !== '', fn ($q) => $q
                ->whereRaw('MATCH(search_index) AGAINST(? IN BOOLEAN MODE)', [$searchTerm])
                ->orderByRaw('MATCH(search_index) AGAINST(? IN BOOLEAN MODE) DESC', [$searchTerm]))
            ->when($request->filled('species_id'), fn ($q) => $q->where('species_id', $request->species_id))
            ->when($request->filled('breed_id'), fn ($q) => $q->where('breed_id', $request->breed_id))
            ->when($request->filled('voivodeship_id'), fn ($q) => $q->where('voivodeship_id', $request->voivodeship_id))
            ->when($request->filled('city_id'), fn ($q) => $q->where('city_id', $request->city_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('color_id'), fn ($q) => $q->whereHas(
                'colors',
                fn ($q2) => $q2->where('colors.id', $request->color_id)
            ))
            ->when($searchTerm === '', fn ($q) => $q->orderBy('created_at', 'desc'))
            ->get();

        // ** Listy słownikowe do formularza filtrów. Miejscowości nie wczytujemy w całości
        // (rejestr SIMC ma ~100 tys. rekordów) — pole podpowiada się przez GET /cities/search,
        // tu dociągamy tylko nazwę ewentualnie już wybranej (z query stringa filtra).
        return view('animals.index', [
            'animals' => $animals,
            'species' => Species::orderBy('sortkey')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'selectedCityName' => City::find($request->city_id)?->name_pl,
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
        ]);
    }

    // Wyświetla mapę wszystkich zatwierdzonych ogłoszeń z pinezkami (GET /map). Filtry działają
    // po stronie przeglądarki (Alpine) — cały zbiór trafia do widoku od razu, dzięki temu zmiana
    // filtra nie resetuje przybliżenia/przesunięcia mapy tak jak przeładowanie strony na /animals.
    public function map(Request $request)
    {
        $animals = Animal::where('mod_status', 'approved')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos', 'colors'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Animal $animal) {
                $mainPhoto = $animal->photos->firstWhere('is_main', true) ?? $animal->photos->first();

                return [
                    'id' => $animal->id,
                    'lat' => (float) $animal->latitude,
                    'lng' => (float) $animal->longitude,
                    'status' => $animal->status,
                    'title' => $animal->generated_title,
                    'location' => $animal->location_text ?: ($animal->city->name_pl ?? null),
                    'thumbnail' => $mainPhoto ? asset('storage/'.$mainPhoto->path) : null,
                    'url' => route('animals.show', $animal),
                    'species_id' => $animal->species_id,
                    'breed_id' => $animal->breed_id,
                    'voivodeship_id' => $animal->voivodeship_id,
                    'city_id' => $animal->city_id,
                    'color_ids' => $animal->colors->pluck('id')->all(),
                ];
            })
            ->values();

        return view('animals.map', [
            'animals' => $animals,
            'species' => Species::orderBy('sortkey')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
            // ** Wstępny filtr statusu z query stringa — obsługuje link "Zobacz na mapie" ze strony głównej
            'initialStatus' => $request->get('status', ''),
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
        $animal->load(['species', 'breed', 'voivodeship', 'city', 'photos', 'colors']);

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

    // Zamienia frazę wpisaną przez użytkownika na zapytanie MySQL BOOLEAN MODE: usuwa znaki
    // specjalne tego trybu (+ - > < ( ) ~ * " @), żeby nie psuły składni, i dla każdego słowa
    // dopisuje "+" (wymagane, żeby kilka słów zwężało wynik, nie rozszerzało) oraz "*" na końcu
    // (dopasowanie po prefiksie, np. "labrad" -> "labrador").
    private function toFulltextBooleanQuery(string $term): string
    {
        $cleaned = preg_replace('/[+\-><()~*"@]+/', ' ', $term);
        $words = array_filter(preg_split('/\s+/', trim($cleaned)), fn ($w) => $w !== '');

        return implode(' ', array_map(fn ($w) => '+'.$w.'*', $words));
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
