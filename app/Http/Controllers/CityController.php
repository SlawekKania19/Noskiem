<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

// ---------------------------
// Wyszukiwanie miejscowości do pola z autouzupełnianiem w formularzu zgłoszenia.
// Lista miejscowości (rejestr SIMC) liczy ok. 100 tys. rekordów, więc nie da się
// jej wysłać do przeglądarki w całości — wyszukujemy na żądanie, po prefiksie nazwy.
// ---------------------------

class CityController extends Controller
{
    public function search(Request $request)
    {
        $data = $request->validate([
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'q'              => 'required|string|min:3|max:100',
        ]);

        $cities = City::where('voivodeship_id', $data['voivodeship_id'])
            ->where('name_pl', 'like', $data['q'].'%')
            ->orderBy('name_pl')
            ->limit(20)
            ->get(['id', 'name_pl']);

        return response()->json($cities);
    }
}
