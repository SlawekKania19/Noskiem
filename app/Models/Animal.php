<?php

namespace App\Models;

use App\Services\TitleGenerator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Model reprezentujący zwierzęta zgłaszane do systemu. Zawiera informacje o statusie, opisie, dacie zdarzenia, lokalizacji oraz kontakcie do zgłaszającego.
// ---------------------------

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

    // ** Relacje

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

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'animal_color');
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class);
    }

    // ** Tytuł liczony na bieżąco z aktualnego szablonu (Ustawienia w panelu), a nie z kolumny
    // `title` zapisanej przy zgłoszeniu/edycji — dzięki temu zmiana szablonu w panelu od razu
    // widoczna jest na wszystkich ogłoszeniach, bez potrzeby ich ponownego zapisywania
    protected function generatedTitle(): Attribute
    {
        return Attribute::get(fn () => TitleGenerator::generate([
            'animal_name'      => $this->animal_name,
            'species_name'     => $this->species?->name_pl,
            'breed_name'       => $this->breed?->breed_pl,
            'city_name'        => $this->city?->name_pl,
            'voivodeship_name' => $this->voivodeship?->name_pl,
            'status'           => $this->status,
        ]));
    }
}
