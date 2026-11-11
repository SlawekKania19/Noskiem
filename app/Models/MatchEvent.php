<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ---------------------------
// Zdarzenie użytkownika przy dopasowaniach (teraz: kliknięcie w link z maila).
// Tylko log - NIE używamy go do zmiany progów ani rankingu (sygnał słaby i obciążony)
// ---------------------------

class MatchEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'match_search_id', 'event', 'source', 'query_animal_edit_id', 'query_animal_id',
        'animal_id', 'position', 'model_version', 'user_agent',
    ];
}
