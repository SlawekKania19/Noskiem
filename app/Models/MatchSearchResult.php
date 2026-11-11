<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Kandydat w logu wyszukiwania - także poniżej progu (shown = false)
// ---------------------------

class MatchSearchResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'match_search_id', 'animal_id', 'score', 'bonus', 'rank', 'level', 'shown',
        'position', 'breed_match', 'color_overlap', 'distance_km', 'best_photo_index',
    ];

    protected $casts = [
        'shown' => 'boolean',
        'breed_match' => 'boolean',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(MatchSearch::class, 'match_search_id');
    }
}
