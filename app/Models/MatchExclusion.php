<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Propozycja AI odrzucona przez moderatora (App\Services\MatchExclusions)
// ---------------------------

class MatchExclusion extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['animal_edit_id', 'animal_id', 'candidate_animal_id', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
