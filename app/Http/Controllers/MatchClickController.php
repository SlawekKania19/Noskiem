<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\MatchEvent;
use App\Models\MatchSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// ---------------------------
// Kliknięcie w ogłoszenie z maila z podobnymi zwierzakami (MatchCandidatesFound).
// Link podpisany (middleware "signed") — nikt nie dopisze fałszywych zdarzeń.
// Zapisuje zdarzenie z pozycją na liście i przekierowuje na ogłoszenie.
// Sygnał słaby i obciążony (pozycja wpływa na klik, skanery poczty otwierają linki
// same) — tylko log, NIE wpływa na progi ani ranking.
// ---------------------------

class MatchClickController extends Controller
{
    public function click(MatchSearch $matchSearch, int $position, Request $request)
    {
        // ** Kandydat z tej pozycji w zalogowanym wyszukiwaniu — URL wskazuje pozycję, nie ogłoszenie,
        // więc podpisany link nie przekieruje nigdzie poza listę z maila
        $result = $matchSearch->results()->where('shown', true)->where('position', $position)->first();

        abort_if($result === null, 404);

        // ** Błąd zapisu logu nie może zablokować przejścia do ogłoszenia
        try {
            MatchEvent::create([
                'match_search_id'      => $matchSearch->id,
                'event'                => 'click',
                'source'               => 'email',
                'query_animal_edit_id' => $matchSearch->query_animal_edit_id,
                'query_animal_id'      => $matchSearch->query_animal_id,
                'animal_id'            => $result->animal_id,
                'position'             => $position,
                'model_version'        => $matchSearch->model_version,
                'user_agent'           => Str::limit((string) $request->userAgent(), 250, ''),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        // ** Ogłoszenie mogło zniknąć (odnalezione, usunięte) od wysłania maila
        $animal = Animal::where('mod_status', 'approved')->find($result->animal_id);

        return $animal
            ? redirect()->route('animals.show', $animal)
            : redirect()->route('animals.index')->with('success', 'To ogłoszenie nie jest już aktywne — zwierzak mógł się już odnaleźć.');
    }
}
