<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    /**
     * Lista zatwierdzonych ogłoszeń.
     * Obsługuje filtry: species, breed, city, voivodeship, status.
     */
    public function index(Request $request)
    {
        $query = Animal::query()
            ->where('is_active', true)
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos']);

        // Filtry
        if ($request->filled('species_id')) {
            $query->where('species_id', $request->species_id);
        }

        if ($request->filled('breed_id')) {
            $query->where('breed_id', $request->breed_id);
        }

        if ($request->filled('voivodeship_id')) {
            $query->where('voivodeship_id', $request->voivodeship_id);
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status); // lost / found
        }

        // Sortowanie
        $query->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    /**
     * Szczegóły jednego ogłoszenia.
     */
    public function show(Animal $animal)
    {
        return $animal->load([
            'species',
            'breed',
            'voivodeship',
            'city',
            'photos'
        ]);
    }

    /**
     * Usuwanie ogłoszenia (tylko admin).
     */
    public function destroy(Animal $animal)
    {
        $animal->delete();

        return response()->json([
            'message' => 'Ogłoszenie zostało usunięte.'
        ]);
    }

    /**
     * Wyszukiwarka pełnotekstowa.
     * Szuka po tytule, opisie, imieniu zwierzęcia i znacznikach.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $q = $request->q;

        return Animal::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('title', 'LIKE', "%$q%")
                      ->orWhere('description', 'LIKE', "%$q%")
                      ->orWhere('animal_name', 'LIKE', "%$q%")
                      ->orWhere('ident_marks', 'LIKE', "%$q%");
            })
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
