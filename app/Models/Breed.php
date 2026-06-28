<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Model reprezentujący rasę zwierzęcia.
// ---------------------------

class Breed extends Model
{
    protected $fillable = [
        'species_id',
        'breed_pl',
        'breed_en',
    ];

    // ** Relacje

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
