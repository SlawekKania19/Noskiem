<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationLog extends Model
{
    // Model reprezentujący logi moderacji zwierząt. Zawiera informacje o akcji moderacyjnej, komentarzu oraz relacje do zwierzęcia i użytkownika, który wykonał akcję.
    protected $fillable = [
        'animal_id',
        'user_id',
        'action',
        'comment',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casty
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'action' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacje
    |--------------------------------------------------------------------------
    */

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
