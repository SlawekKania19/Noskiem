<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ---------------------------
// Log maili do autorów starych ogłoszeń o nowym podobnym ogłoszeniu
// (patrz App\Services\MatchOwnerNotifier). Status: sent | cooldown
// ---------------------------

class MatchOwnerNotice extends Model
{
    // ** Log tylko dopisywany - bez updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'match_search_id',
        'animal_id',
        'query_animal_id',
        'level',
        'status',
    ];
}
