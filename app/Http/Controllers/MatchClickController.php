<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\MatchEvent;
use App\Models\MatchSearch;
use App\Models\MatchSearchResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// ---------------------------
// Kliknięcia w ogłoszenia z maili z dopasowaniami. Linki podpisane (middleware "signed") -
// nikt nie dopisze fałszywych zdarzeń. Zapis zdarzenia, potem przekierowanie.
// - click      - mail do autora NOWEGO ogłoszenia (MatchCandidatesFound), prowadzi do kandydata
// - ownerClick - mail do autora STAREGO ogłoszenia (NewSimilarListing), prowadzi do nowego
// Sygnał słaby i obciążony (pozycja wpływa na klik, skanery poczty otwierają linki
// same) - tylko log, NIE wpływa na progi ani ranking.
// ---------------------------

class MatchClickController extends Controller
{
    public function click(MatchSearch $matchSearch, int $position, Request $request)
    {
        $result = $this->shownResult($matchSearch, $position);

        $this->logClick($matchSearch, $result, 'email', $request);

        return $this->redirectTo($result->animal_id);
    }

    public function ownerClick(MatchSearch $matchSearch, int $position, Request $request)
    {
        // ** Pozycja wskazuje adresata (stare ogłoszenie), cel to ogłoszenie z zapytania
        $result = $this->shownResult($matchSearch, $position);

        $this->logClick($matchSearch, $result, 'email_owner', $request);

        return $this->redirectTo($matchSearch->query_animal_id);
    }

    // ** Kandydat z tej pozycji w zalogowanym wyszukiwaniu - URL wskazuje pozycję, nie ogłoszenie,
    // więc podpisany link nie przekieruje nigdzie poza listę z maila
    protected function shownResult(MatchSearch $matchSearch, int $position): MatchSearchResult
    {
        $result = $matchSearch->results()->where('shown', true)->where('position', $position)->first();

        abort_if($result === null, 404);

        return $result;
    }

    protected function logClick(MatchSearch $matchSearch, MatchSearchResult $result, string $source, Request $request): void
    {
        // ** Błąd zapisu logu nie może zablokować przejścia do ogłoszenia
        try {
            MatchEvent::create([
                'match_search_id'      => $matchSearch->id,
                'event'                => 'click',
                'source'               => $source,
                'query_animal_edit_id' => $matchSearch->query_animal_edit_id,
                'query_animal_id'      => $matchSearch->query_animal_id,
                'animal_id'            => $result->animal_id,
                'position'             => $result->position,
                'model_version'        => $matchSearch->model_version,
                'user_agent'           => Str::limit((string) $request->userAgent(), 250, ''),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function redirectTo(?int $animalId)
    {
        // ** Ogłoszenie mogło zniknąć (odnalezione, usunięte) od wysłania maila
        $animal = $animalId ? Animal::where('mod_status', 'approved')->find($animalId) : null;

        return $animal
            ? redirect()->route('animals.show', $animal)
            : redirect()->route('animals.index')->with('success', 'To ogłoszenie nie jest już aktywne - zwierzak mógł się już odnaleźć.');
    }
}
