<?php

namespace App\Models;

use App\Services\PhoneFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\Stats;

// ---------------------------
// Model reprezentujący zgłoszenie "też widziałem" pod ogłoszeniem (Animal ze
// statusem "found"). Przechodzi tę samą moderację co ogłoszenia (patrz
// SightingController, SightingModerationService), ale po zatwierdzeniu nie
// staje się osobnym ogłoszeniem - pojawia się jako wpis w timeline pod
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

    // ** Tokeny/kontakt/IP - dane wrażliwe, nigdy w JSON, tak samo jak w Animal
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

    // ** Dziennik statystyk (App\Support\Stats) - od potwierdzenia e-maila, województwo z ogłoszenia
    protected static function booted(): void
    {
        $record = fn (Sighting $sighting) => Stats::record('sighting_submitted', [
            'listing_status' => $sighting->animal?->status,
            'species_id'     => $sighting->species_id,
            'voivodeship_id' => $sighting->animal?->voivodeship_id,
            'subject_id'     => $sighting->id,
        ]);

        static::created(fn (Sighting $sighting) => $sighting->email_verified_at !== null ? $record($sighting) : null);

        static::updated(function (Sighting $sighting) use ($record) {
            if ($sighting->wasChanged('email_verified_at') && $sighting->getOriginal('email_verified_at') === null && $sighting->email_verified_at !== null) {
                $record($sighting);
            }
        });
    }

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

    // Numer telefonu w czytelnym formacie ("+48 600-123-456"), niezależnie od zapisu autora
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(fn () => PhoneFormatter::format($this->contact_phone));
    }
}
