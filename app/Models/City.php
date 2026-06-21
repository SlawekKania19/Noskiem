<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Model reprezentujący miasto, które może mieć wiele zwierząt.

class City extends Model
{
    protected $fillable = [
        'name_pl',
        'name_en',
        'voivodeship_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacje
    |--------------------------------------------------------------------------
    */

    public function voivodeship(): BelongsTo
    {
        return $this->belongsTo(Voivodeship::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
