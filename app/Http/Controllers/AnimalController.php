<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    /**
     * Lista zatwierdzonych ogłoszeń.
     */
    public function index()
    {
        return Animal::with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
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
     * Usunięcie ogłoszenia (opcjonalnie tylko dla admina).
     */
    public function destroy(Animal $animal)
    {
        // Usuwamy zdjęcia fizycznie
        foreach ($animal->photos as $photo) {
            \Storage::disk('public')->delete($photo->path);
        }

        $animal->delete();

        return response()->json([
            'message' => 'Ogłoszenie zostało usunięte.',
        ]);
    }
}
