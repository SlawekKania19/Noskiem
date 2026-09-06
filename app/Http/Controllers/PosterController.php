<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Services\PosterRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// ---------------------------
// Plakat ogłoszenia w PDF.
// Dostępny publicznie (także dla osób, które nie są autorem ogłoszenia —
// ktoś, kto chce pomóc, też może chcieć wydrukować plakat). Tylko ogłoszenia
// zatwierdzone. Wybór formatu (A4 / B5) przez parametr ?format=.
// ---------------------------

class PosterController extends Controller
{
    public function show(Animal $animal, Request $request, PosterRenderer $renderer): Response
    {
        abort_unless($animal->mod_status === 'approved', 404);

        $animal->load(['species', 'breed', 'voivodeship', 'city', 'colors', 'photos']);

        $format = (string) $request->query('format', 'a4');

        // ** Inline (nie "attachment") — plakat otwiera się w nowej karcie,
        // użytkownik od razu widzi podgląd i drukuje
        return $renderer->render($animal, $format)
            ->stream("plakat-{$animal->id}.pdf");
    }
}
