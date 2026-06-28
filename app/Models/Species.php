<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Model reprezentujący gatunek zwierzęcia. Zawiera informacje o nazwie gatunku w języku polskim i angielskim, a także relacje do ras i zwierząt należących do tego gatunku.
// ---------------------------

class Species extends Model
{
    protected $fillable = [
        'name_pl',
        'name_en',
    ];

    // ** Relacje

    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
