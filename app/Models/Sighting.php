<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Model reprezentujący zgłoszenie "też widziałem" pod ogłoszeniem (Animal ze
// statusem "found"). Przechodzi tę samą moderację co ogłoszenia (patrz
// SightingController, SightingModerationService), ale po zatwierdzeniu nie
// staje się osobnym ogłoszeniem — pojawia się jako wpis w timeline pod
// oryginałem (patrz animals/show.blade.php).
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
        'mod_status',
        'mod_reject_reason',
        'edit_token',
        'email_verified_at',
        'submitter_ip',
    ];

    // ** Tokeny/kontakt/IP — dane wrażliwe, nigdy w JSON, tak samo jak w Animal
    protected $hidden = [
        'edit_token',
        'submitter_ip',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'date_seen' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'email_verified_at' => 'datetime',
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

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
