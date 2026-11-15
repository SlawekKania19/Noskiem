<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Support\Stats;

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
        'approved_at',
        'email_verified_at',
        'status',
        'title',
        'description',
        'animal_name',
        'ident_marks',
        'behavior',
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
        'submitter_ip',
    ];

    // ** Nigdy w JSON (m.in. publiczne /api/animal-edits/*) - edit_token daje pełny
    // dostęp do edycji/usunięcia ogłoszenia bez logowania, submitter_ip/kontakt to
    // dane wrażliwe zgłaszającego, który czeka na moderację.
    protected $hidden = [
        'edit_token',
        'submitter_ip',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'chip_present' => 'boolean',
        'date_event' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'approved_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    // ** Relacje

    // ** Dziennik statystyk (App\Support\Stats) - tylko nowe zgłoszenia (bez animal_id), nie edycje.
    // Zgłoszenie liczymy od potwierdzenia e-maila - niepotwierdzone to zwykle spam/porzucone
    protected static function booted(): void
    {
        static::created(function (AnimalEdit $edit) {
            if ($edit->animal_id === null && $edit->email_verified_at !== null) {
                Stats::record('animal_submitted', Stats::fromListing($edit));
            }
        });

        static::updated(function (AnimalEdit $edit) {
            if ($edit->animal_id !== null) {
                return;
            }

            if ($edit->wasChanged('email_verified_at') && $edit->getOriginal('email_verified_at') === null && $edit->email_verified_at !== null) {
                Stats::record('animal_submitted', Stats::fromListing($edit));
            }

            if ($edit->wasChanged('mod_status') && $edit->mod_status === 'rejected') {
                Stats::record('animal_rejected', Stats::fromListing($edit));
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

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'animal_edit_color');
    }

    // ** Inne zgłoszenia z tego samego adresu e-mail (Animal + AnimalEdit) - pomocne
    // przy moderacji, żeby wykryć próby "podbicia" pozycji przez wielokrotne dodawanie
    // tego samego zwierzaka. Przy edycji istniejącego ogłoszenia wyklucza samo siebie
    // (animal_id) - to nie jest duplikat, tylko rekord poddawany właśnie moderacji.
    public function relatedByContactEmail(int $limit = 5)
    {
        return \App\Services\RelatedSubmissionsFinder::find($this->contact_email, $this->animal_id, $this->id, $limit);
    }
}
