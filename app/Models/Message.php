<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Model dla wiadomości od użytkowników zainteresowanych adopcją zwierząt. Zawiera informacje o imieniu, emailu i treści wiadomości, a także odniesienie do zwierzęcia, którego dotyczy wiadomość.
// ---------------------------

class Message extends Model
{
    protected $fillable = [
        'animal_id',
        'sighting_id',
        'name',
        'email',
        'message',
        'submitter_ip',
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
}
