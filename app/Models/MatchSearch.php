<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Log jednego wyszukiwania podobnych zwierząt (patrz App\Services\MatchSearchLogger)
// ---------------------------

class MatchSearch extends Model
{
    // ** Log tylko dopisywany — bez updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'match_request_id',
        'query_animal_edit_id',
        'query_animal_id',
        'model_version',
        'params',
        'n_candidates',
        'n_compared',
        'n_pending_embedding',
        'n_shown',
        'channel',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(MatchSearchResult::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }
}
