<?php

namespace App\Models;

use App\Services\PhoneFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Model wpisu w bezpłatnej bazie chipów. Niezależny od ogłoszeń (Animal) - po
// zatwierdzeniu jest wyszukiwalny wyłącznie po numerze chipa. Edycja przez
// właściciela (token) ląduje w pending_changes i wymaga ponownej moderacji.
// ---------------------------

class ChipRegistration extends Model
{
    // ** Polecana długość numeru wg ISO 11784/11785 - krótsze (starsze chipy) dopuszczamy
    public const STANDARD_CHIP_LENGTH = 15;

    // ** Pola, które właściciel może zmienić przez link z tokenem (trafiają do pending_changes)
    public const EDITABLE_FIELDS = [
        'chip_number',
        'animal_name',
        'species_id',
        'breed_id',
        'voivodeship_id',
        'city_id',
        'contact_phone',
        'contact_email',
        'additional_info',
        'sticker_photo_path',
    ];

    protected $fillable = [
        'chip_number',
        'animal_name',
        'species_id',
        'breed_id',
        'voivodeship_id',
        'city_id',
        'contact_phone',
        'contact_email',
        'additional_info',
        'sticker_photo_path',
        'mod_status',
        'mod_reject_reason',
        'pending_changes',
        'edit_token',
        'email_verified_at',
        'submitter_ip',
    ];

    // ** Tokeny/kontakt/IP/naklejka - dane wrażliwe, nigdy w JSON
    protected $hidden = [
        'edit_token',
        'submitter_ip',
        'contact_email',
        'contact_phone',
        'sticker_photo_path',
        'pending_changes',
    ];

    protected $casts = [
        'pending_changes' => 'array',
        'email_verified_at' => 'datetime',
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

    // Numer telefonu w czytelnym formacie ("+48 600-123-456"), niezależnie od zapisu właściciela
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(fn () => PhoneFormatter::format($this->contact_phone));
    }

    // ** Pliki naklejek (bieżąca + z oczekującej edycji) znikają razem z wpisem -
    // bez tego zostawałyby osierocone pliki na dysku prywatnym
    protected static function booted(): void
    {
        static::deleted(function (ChipRegistration $registration) {
            Storage::disk('local')->delete(array_filter([
                $registration->sticker_photo_path,
                $registration->pending_changes['sticker_photo_path'] ?? null,
            ]));
        });
    }

    // Inne zatwierdzone wpisy z tym samym numerem chipa
    public function conflictingRegistrations(): Builder
    {
        return static::where('chip_number', $this->chip_number)
            ->where('mod_status', 'approved')
            ->whereKeyNot($this->id);
    }

    // Czy ten sam numer chipa jest już zatwierdzony w innym wpisie - sygnał dla
    // moderatora, żeby rozstrzygnął na podstawie zdjęcia naklejki
    public function hasChipConflict(): bool
    {
        return $this->conflictingRegistrations()->exists();
    }
}
