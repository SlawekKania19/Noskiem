<?php

namespace App\Models;

use App\Services\MatchExclusions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Model reprezentujący logi moderacji zwierząt. Zawiera informacje o akcji moderacyjnej, komentarzu oraz relacje do zwierzęcia i użytkownika, który wykonał akcję.
// ---------------------------

class ModerationLog extends Model
{
    protected $fillable = [
        'animal_id',
        'animal_edit_id',
        'sighting_id',
        'chip_registration_id',
        'user_id',
        'action',
        'comment',
    ];

    // ** Po zatwierdzeniu zgłoszenia odrzucone propozycje AI przechodzą na nowe ogłoszenie
    protected static function booted(): void
    {
        static::created(function (ModerationLog $log) {
            if ($log->action === 'approved' && $log->animal_edit_id && $log->animal_id) {
                app(MatchExclusions::class)->carryOver($log->animal_edit_id, $log->animal_id);
            }
        });
    }

    // ** Casty

    protected $casts = [
        'action' => 'string',
    ];

    // ** Relacje

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function sighting(): BelongsTo
    {
        return $this->belongsTo(Sighting::class);
    }

    public function chipRegistration(): BelongsTo
    {
        return $this->belongsTo(ChipRegistration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
