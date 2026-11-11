<?php

namespace App\Http\Controllers;

use App\Models\Animal;

// ---------------------------
// Wypisanie się z maili "nowe ogłoszenie podobne do Twojego" (NewSimilarListing) -
// dla jednego ogłoszenia, bez logowania. Link podpisany (bez wygasania).
// GET tylko pokazuje przycisk, zapis dopiero po POST - skanery poczty same otwierają
// linki z maili i bez tego wypisywałyby autorów za nich.
// ---------------------------

class MatchOptOutController extends Controller
{
    public function show(Animal $animal)
    {
        return view('matches.opt-out', [
            'animal' => $animal,
            'done'   => $animal->match_notify_opt_out_at !== null,
        ]);
    }

    public function store(Animal $animal)
    {
        if ($animal->match_notify_opt_out_at === null) {
            // ** Z pominięciem Eloquenta - bez ruszania updated_at i search_index
            Animal::whereKey($animal->id)->toBase()->update(['match_notify_opt_out_at' => now()]);
        }

        return view('matches.opt-out', ['animal' => $animal, 'done' => true]);
    }
}
