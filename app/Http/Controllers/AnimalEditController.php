<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\Breed;
use App\Models\City;
use App\Models\Color;
use App\Models\Species;
use App\Models\Voivodeship;
use App\Services\ImageCompressor;
use App\Services\TitleGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// ---------------------------
// Kontroler do obsługi zgłoszeń edycji zwierząt (AnimalEdit)
// ---------------------------

class AnimalEditController extends Controller
{
    // Wyświetla listę zgłoszeń edycji zwierząt oczekujących na moderację
    public function indexPending()
    {
        return AnimalEdit::where('mod_status', 'pending')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Wyświetla szczegóły konkretnego zgłoszenia edycji zwierzęcia, w tym informacje o gatunku, rasie, województwie, mieście, powiązanym ogłoszeniu i zdjęciach. Zwraca dane w formacie JSON.
    public function show(AnimalEdit $animalEdit)
    {
        return $animalEdit->load(['species', 'breed', 'voivodeship', 'city', 'animal', 'photos']);
    }

    // Wyświetla formularz zgłoszenia nowego ogłoszenia wraz z listami słownikowymi do pól select.
    // Miejscowości nie wczytujemy w całości (rejestr SIMC ma ~100 tys. rekordów) — pole
    // podpowiada się przez GET /cities/search, tu dociągamy tylko nazwę ewentualnie już wybranej.
    public function create()
    {
        return view('animals.create', [
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
            'selectedCityName' => City::find(old('city_id'))?->name_pl,
        ]);
    }

    // Zapisuje nowe zgłoszenie edycji zwierzęcia wraz ze zdjęciami, walidując dane wejściowe i ustawiając status moderacji na "pending". Generuje unikalny token edycji i przekierowuje użytkownika z komunikatem o powodzeniu operacji.
    public function store(Request $request)
    {
        $data = $request->validate([
            'status'         => 'required|in:lost,found',
            'description'    => 'required|string',
            'animal_name'    => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'ident_marks'    => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => ['nullable', 'max:50', 'regex:/^[0-9]*$/'],
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'        => [
                'required',
                Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id')),
            ],
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:20',
            'photos'         => 'nullable|array|max:6',
            'photos.*'       => 'image|max:5120',
            'main_photo_index' => 'nullable|integer|min:0',
            'colors'         => 'nullable|array',
            'colors.*'       => 'integer|exists:colors,id',
            'accept_terms'   => 'accepted',
        ], [
            'animal_name.regex' => 'Imię zwierzaka może zawierać tylko litery.',
            'chip_number.regex' => 'Numer chipa może zawierać tylko cyfry.',
        ]);

        // ** Zdjęcia, kolory i zgoda na regulamin są obsługiwane osobno — nie należą do fillable AnimalEdit
        $photos = $data['photos'] ?? [];
        $colors = $data['colors'] ?? [];
        $mainPhotoIndex = (int) ($data['main_photo_index'] ?? 0);
        unset($data['photos'], $data['colors'], $data['main_photo_index'], $data['accept_terms']);

        // ** Spoza zakresu (np. przy manipulacji formularzem) — wraca do pierwszego zdjęcia
        if ($mainPhotoIndex < 0 || $mainPhotoIndex >= count($photos)) {
            $mainPhotoIndex = 0;
        }

        $data['title'] = $this->generateTitle($data);
        $data['mod_status'] = 'pending';
        $data['edit_token'] = Str::uuid();

        $animalEdit = AnimalEdit::create($data);
        $animalEdit->colors()->sync($colors);

        // ** Zapis plików do storage/app/public (poza public/) + rekordy w tabeli photos
        // Zdjęcia są przeskalowane i skompresowane (App\Services\ImageCompressor), żeby
        // duże pliki z telefonów nie zapychały storage
        foreach ($photos as $index => $photo) {
            $path = ImageCompressor::store($photo, 'photos', 'public');

            $animalEdit->photos()->create([
                'path' => $path,
                'is_main' => $index === $mainPhotoIndex,
            ]);
        }

        return redirect()->route('animals.index')
            ->with('success', 'Zgłoszenie zostało wysłane i oczekuje na moderację.');
    }

    // Wyświetla formularz edycji konkretnego ogłoszenia zwierzęcia, sprawdzając poprawność tokenu edycji. Jeśli token jest nieprawidłowy, zwraca błąd 403. Zwraca widok z formularzem edycji.
    public function edit(Animal $animal, Request $request)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu do edycji.');
        }

        $animal->load(['photos', 'colors']);

        $selectedCityId = old('city_id', $animal->city_id);

        return view('animals.edit', [
            'animal' => $animal,
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
            'selectedCityName' => City::find($selectedCityId)?->name_pl,
        ]);
    }

    // Aktualizuje konkretne ogłoszenie zwierzęcia na podstawie zgłoszenia edycji, sprawdzając poprawność tokenu edycji. Jeśli token jest nieprawidłowy, zwraca błąd 403. Waliduje dane wejściowe, tworzy nowe zgłoszenie edycji i przekierowuje użytkownika z komunikatem o powodzeniu operacji.
    public function update(Request $request, Animal $animal)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu do edycji.');
        }

        $data = $request->validate([
            'status'         => 'required|in:lost,found',
            'description'    => 'required|string',
            'animal_name'    => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'ident_marks'    => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => ['nullable', 'max:50', 'regex:/^[0-9]*$/'],
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'        => [
                'required',
                Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id')),
            ],
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:20',
            'colors'         => 'nullable|array',
            'colors.*'       => 'integer|exists:colors,id',
        ], [
            'animal_name.regex' => 'Imię zwierzaka może zawierać tylko litery.',
            'chip_number.regex' => 'Numer chipa może zawierać tylko cyfry.',
        ]);

        $colors = $data['colors'] ?? [];
        unset($data['colors']);

        $data['title']      = $this->generateTitle($data);
        $data['animal_id']  = $animal->id;
        $data['mod_status'] = 'pending';
        $data['edit_token'] = $animal->edit_token;

        $animalEdit = AnimalEdit::create($data);
        $animalEdit->colors()->sync($colors);

        return redirect()->route('animals.show', $animal)
            ->with('success', 'Edycja została wysłana i oczekuje na moderację.');
    }

    // Wylicza tytuł ogłoszenia z szablonu w ustawieniach (App\Filament\Pages\Settings) —
    // użytkownik formularza publicznego tytułu nie wpisuje samodzielnie
    private function generateTitle(array $data): string
    {
        return TitleGenerator::generate([
            'animal_name'      => $data['animal_name'],
            'species_name'     => Species::find($data['species_id'])?->name_pl,
            'breed_name'       => Breed::find($data['breed_id'])?->breed_pl,
            'city_name'        => City::find($data['city_id'])?->name_pl,
            'voivodeship_name' => Voivodeship::find($data['voivodeship_id'])?->name_pl,
            'status'           => $data['status'],
        ]);
    }
}
