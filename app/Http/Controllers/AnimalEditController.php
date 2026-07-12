<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\Breed;
use App\Models\City;
use App\Models\Species;
use App\Models\Voivodeship;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    // Wyświetla formularz zgłoszenia nowego ogłoszenia wraz z listami słownikowymi do pól select
    public function create()
    {
        return view('animals.create', [
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'cities' => City::orderBy('name_pl')->get(),
        ]);
    }

    // Zapisuje nowe zgłoszenie edycji zwierzęcia wraz ze zdjęciami, walidując dane wejściowe i ustawiając status moderacji na "pending". Generuje unikalny token edycji i przekierowuje użytkownika z komunikatem o powodzeniu operacji.
    public function store(Request $request)
    {
        $data = $request->validate([
            'status'         => 'required|in:lost,found',
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'animal_name'    => 'required|string|max:255',
            'ident_marks'    => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => 'nullable|string|max:50',
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'        => 'required|exists:cities,id',
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:20',
            'photos'         => 'nullable|array|max:6',
            'photos.*'       => 'image|max:5120',
        ]);

        // ** Zdjęcia są obsługiwane osobno — nie należą do fillable AnimalEdit
        $photos = $data['photos'] ?? [];
        unset($data['photos']);

        $data['mod_status'] = 'pending';
        $data['edit_token'] = Str::uuid();

        $animalEdit = AnimalEdit::create($data);

        // ** Zapis plików do storage/app/public (poza public/) + rekordy w tabeli photos
        foreach ($photos as $index => $photo) {
            $path = $photo->store('photos', 'public');

            $animalEdit->photos()->create([
                'path' => $path,
                'is_main' => $index === 0,
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

        $animal->load('photos');

        return view('animals.edit', [
            'animal' => $animal,
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'cities' => City::orderBy('name_pl')->get(),
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
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'animal_name'    => 'required|string|max:255',
            'ident_marks'    => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => 'nullable|string|max:50',
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'        => 'required|exists:cities,id',
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:20',
        ]);

        $data['animal_id']  = $animal->id;
        $data['mod_status'] = 'pending';
        $data['edit_token'] = $animal->edit_token;

        AnimalEdit::create($data);

        return redirect()->route('animals.show', $animal)
            ->with('success', 'Edycja została wysłana i oczekuje na moderację.');
    }
}
