<?php

namespace App\Models;

use App\Services\PhoneFormatter;
use App\Services\TitleGenerator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    // ** Nigdy w JSON (m.in. publiczne /api/animals) — edit_token daje pełny dostęp
    // do edycji/usunięcia ogłoszenia bez logowania, submitter_ip/kontakt to dane
    // wrażliwe. search_index to wewnętrzny szczegół implementacji wyszukiwarki.
    protected $hidden = [
        'edit_token',
        'submitter_ip',
        'contact_email',
        'contact_phone',
        'search_index',
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

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }

    // ** Wyszukiwanie pełnotekstowe
    //
    // `search_index` to zdenormalizowany tekst złożony z pól, po których ma sens szukać
    // (nazwa, opis, znaki szczególne, zachowanie, nazwy gatunku/rasy/miasta/województwa,
    // kolory) — indeksowany FULLTEXT-em w MySQL. Odświeżamy go automatycznie po każdym
    // zapisie modelu; kolory (relacja many-to-many) trzeba jeszcze doczyścić ręcznym
    // wywołaniem syncSearchIndex() po ich sync()/attach() w miejscu, gdzie to się dzieje
    // (ModerationService::approve()), bo pivot nie jest częścią cyklu zapisu modelu.
    protected static function booted(): void
    {
        static::saved(fn (Animal $animal) => $animal->syncSearchIndex());
    }

    public function syncSearchIndex(): void
    {
        $index = $this->buildSearchIndex();

        if ($index !== $this->search_index) {
            $this->search_index = $index;
            $this->saveQuietly();
        }
    }

    protected function buildSearchIndex(): string
    {
        $parts = [
            $this->animal_name,
            $this->ident_marks,
            $this->behavior,
            $this->description,
            $this->species?->name_pl,
            $this->breed?->breed_pl,
            $this->city?->name_pl,
            $this->voivodeship?->name_pl,
            $this->colors->pluck('name')->implode(' '),
        ];

        return Str::of(implode(' ', array_filter($parts, fn ($part) => filled($part))))
            ->squish()
            ->value();
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

    // Numer telefonu w czytelnym formacie ("+48 600-123-456"), niezależnie od zapisu autora
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(fn () => PhoneFormatter::format($this->contact_phone));
    }
}
