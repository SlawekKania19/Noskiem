<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Model reprezentujący zgłoszenie o widzeniu zwierzęcia
// ---------------------------

class Sighting extends Model
{
    protected $fillable = [
        'animal_id',
        'description',
        'special_marks',
        'date_seen',
        'species_id',
        'location',
        'latitude',
        'longitude',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'date_seen' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // ** Relacje

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }
}
