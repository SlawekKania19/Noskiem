<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Model reprezentujący województwo, które może mieć wiele miast i zwierząt.
// ---------------------------

class Voivodeship extends Model
{
    protected $fillable = [
        'name_pl',
        'name_en',
    ];

    // ** Relacje

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
