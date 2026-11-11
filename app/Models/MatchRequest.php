<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Zlecenie wyszukania podobnych zwierząt dla nowego zgłoszenia i wysłania maila
// z kandydatami - patrz App\Services\PhotoMatchNotifier
// ---------------------------

class MatchRequest extends Model
{
    protected $fillable = [
        'animal_edit_id',
        'status',
        'attempts',
        'last_error',
        'results_shown',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function animalEdit(): BelongsTo
    {
        return $this->belongsTo(AnimalEdit::class);
    }
}
