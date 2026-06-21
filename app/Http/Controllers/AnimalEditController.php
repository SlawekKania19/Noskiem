<?php

namespace App\Http\Controllers;

use App\Models\AnimalEdit;
use Illuminate\Http\Request;

class AnimalEditController extends Controller
{
    /**
     * Lista wszystkich edycji oczekujących na moderację.
     */
    public function indexPending()
    {
        return AnimalEdit::where('mod_status', 'pending')
            ->with(['species', 'breed', 'voivodeship', 'city', 'animal'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Pobranie konkretnej edycji.
     */
    public function show(AnimalEdit $animalEdit)
    {
        return $animalEdit->load([
            'species',
            'breed',
            'voivodeship',
            'city',
            'animal',
            'photos'
        ]);
    }

    /**
     * Tworzenie nowej edycji (nowe ogłoszenie lub edycja istniejącego).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id'     => 'nullable|exists:animals,id',
            'status'        => 'required|in:lost,found',
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'animal_name'   => 'nullable|string|max:255',
            'ident_marks'   => 'nullable|string',
            'chip_present'  => 'boolean',
            'chip_number'   => 'nullable|string|max:255',
            'species_id'    => 'required|exists:species,id',
            'breed_id'      => 'required|exists:breeds,id',
            'date_event'    => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'       => 'required|exists:cities,id',
            'location_text' => 'required|string|max:255',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'contact_name'  => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'photos.*'      => 'image|max:4096',
        ]);

        // Tworzymy pending edit
        $edit = AnimalEdit::create([
            ...$validated,
            'mod_status' => 'pending',
            'edit_token' => bin2hex(random_bytes(16)),
        ]);

        // Upload zdjęć
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                $path = $photoFile->store('animals_pending', 'public');

                $edit->photos()->create([
                    'path' => $path,
                    'is_main' => false,
                    'animal_id' => null,
                    'animal_edit_id' => $edit->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Zgłoszenie zostało wysłane do moderacji.',
            'edit_id' => $edit->id,
        ]);
    }
}
