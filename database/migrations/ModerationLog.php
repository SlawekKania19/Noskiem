<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model reprezentujący wpis w logu moderacji.

class ModerationLog extends Model
{
    protected $fillable = [
        'animal_id',
        'user_id',
        'action',
        'comment',
    ];

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
