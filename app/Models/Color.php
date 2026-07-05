<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// ---------------------------
// Model reprezentujący kolor zwierzęcia. Kolory są unikalne i mogą być przypisane do wielu zwierząt (relacja wiele do wielu).
// ---------------------------

class Color extends Model
{
    protected $fillable = [
        'name',
    ];

    // ** Relacje

    public function animals(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'animal_color');
    }
}
