<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\Request;

// ---------------------------
// Kontroler dla zarządzania ogłoszeniami zwierząt. Zawiera metody do wyświetlania listy ogłoszeń, wyświetlania szczegółów ogłoszenia oraz usuwania ogłoszenia.
// ---------------------------

class AnimalController extends Controller
{
    // Wyświetla listę wszystkich ogłoszeń zwierząt, które zostały zatwierdzone przez moderatora. Zwraca dane w formacie JSON.
    public function index()
    {
        return Animal::where('mod_status', 'approved')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Wyświetla szczegóły konkretnego ogłoszenia zwierzęcia, w tym informacje o gatunku, rasie, województwie, mieście i zdjęciach. Zwraca dane w formacie JSON.
    public function show(Animal $animal)
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
