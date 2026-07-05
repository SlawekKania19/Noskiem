<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalEdit;
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

    // Zatwierdza zgłoszenie edycji zwierzęcia, aktualizując powiązane ogłoszenie i ustawiając status moderacji na "approved". Zwraca odpowiedź w formacie JSON z komunikatem o powodzeniu operacji.
    public function create()
    {
        return view('animals.create');
    }

    // Zapisuje nowe zgłoszenie edycji zwierzęcia, walidując dane wejściowe i ustawiając status moderacji na "pending". Generuje unikalny token edycji i przekierowuje użytkownika z komunikatem o powodzeniu operacji.
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
        ]);

        $data['mod_status'] = 'pending';
        $data['edit_token'] = Str::uuid();

        AnimalEdit::create($data);

        return redirect()->route('animals.index')
            ->with('success', 'Zgłoszenie zostało wysłane i oczekuje na moderację.');
    }

    // Wyświetla formularz edycji konkretnego ogłoszenia zwierzęcia, sprawdzając poprawność tokenu edycji. Jeśli token jest nieprawidłowy, zwraca błąd 403. Zwraca widok z formularzem edycji.    
    public function edit(Animal $animal, Request $request)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu do edycji.');
        }

        return view('animals.edit', compact('animal'));
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
