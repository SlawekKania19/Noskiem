<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Model reprezentujący zwierzęta zgłaszane do systemu. Zawiera informacje o statusie, opisie, dacie zdarzenia, lokalizacji oraz kontakcie do zgłaszającego.

class Animal extends Model
{
    protected $fillable = [
        'mod_status',
        'status',
        'title',
        'description',
        'animal_name',
        'ident_marks',
        'chip_present',
        'chip_number',
        'species_id',
        'breed_id',
        'date_event',
        'voivodeship_id',
        'city_id',
        'location_text',
        'latitude',
        'longitude',
        'contact_name',
        'contact_email',
        'contact_phone',
        'edit_token',
    ];

    protected $casts = [
        'chip_present' => 'boolean',
        'date_event' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacje
    |--------------------------------------------------------------------------
    */

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function voivodeship(): BelongsTo
    {
        return $this->belongsTo(Voivodeship::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class);
    }
}
