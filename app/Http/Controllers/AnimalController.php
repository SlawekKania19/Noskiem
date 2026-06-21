<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnimalController extends Controller
{
    /**
     * Lista wszystkich zwierząt.
     */
    public function index()
    {
        return Animal::with(['species', 'breed', 'voivodship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Dodanie nowego ogłoszenia.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'animal_name'    => 'nullable|string|max:255',
            'ident_marks'    => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => 'nullable|string|max:255',
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodship_id'  => 'required|exists:voivodships,id',
            'city_id'        => 'required|exists:cities,id',
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:255',
            'status'         => 'required|in:lost,found',
            'photos.*'       => 'image|max:4096',
        ]);

        $animal = Animal::create($validated);

        // Upload zdjęć
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                $path = $photoFile->store('animals', 'public');

                $animal->photos()->create([
                    'path' => $path,
                    'is_main' => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Ogłoszenie zostało utworzone.',
            'animal'  => $animal->load('photos'),
        ]);
    }

    /**
     * Wyświetlenie jednego ogłoszenia.
     */
    public function show(Animal $animal)
    {
        return $animal->load(['species', 'breed', 'voivodship', 'city', 'photos']);
    }

    /**
     * Aktualizacja ogłoszenia.
     */
    public function update(Request $request, Animal $animal)
    {
        $validated = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'description'    => 'sometimes|string',
            'animal_name'    => 'nullable|string|max:255',
            'ident_marks'    => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => 'nullable|string|max:255',
            'species_id'     => 'sometimes|exists:species,id',
            'breed_id'       => 'sometimes|exists:breeds,id',
            'date_event'     => 'sometimes|date',
            'voivodship_id'  => 'sometimes|exists:voivodships,id',
            'city_id'        => 'sometimes|exists:cities,id',
            'location_text'  => 'sometimes|string|max:255',
            'latitude'       => 'sometimes|numeric',
            'longitude'      => 'sometimes|numeric',
            'contact_name'   => 'sometimes|string|max:255',
            'contact_email'  => 'sometimes|email|max:255',
            'contact_phone'  => 'nullable|string|max:255',
            'status'         => 'sometimes|in:lost,found',
            'photos.*'       => 'image|max:4096',
        ]);

        $animal->update($validated);

        // Upload nowych zdjęć
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                $path = $photoFile->store('animals', 'public');

                $animal->photos()->create([
                    'path' => $path,
                    'is_main' => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Ogłoszenie zostało zaktualizowane.',
            'animal'  => $animal->load('photos'),
        ]);
    }

    /**
     * Usunięcie ogłoszenia.
     */
    public function destroy(Animal $animal)
    {
        // Usuwamy zdjęcia fizycznie
        foreach ($animal->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $animal->delete();

        return response()->json([
            'message' => 'Ogłoszenie zostało usunięte.',
        ]);
    }
}
