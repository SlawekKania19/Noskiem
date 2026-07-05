<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Model reprezentujący edycję zgłoszenia zwierzęcia.
// ---------------------------

class AnimalEdit extends Model
{
    protected $table = 'animal_edits';

    protected $fillable = [
        'animal_id',
        'mod_status',
        'mod_reject_reason',
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

    // ** Relacje

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

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

    public function photos()
    {
        return $this->hasMany(\App\Models\Photo::class, 'animal_edit_id');
    }

}
