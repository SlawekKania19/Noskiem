<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Model reprezentujący zdjęcie zwierzęcia.
// ---------------------------

class Photo extends Model
{
    protected $fillable = [
        'animal_id',
        'path',
        'is_main',
    ];

    // ** Casty

    protected $casts = [
        'is_main' => 'boolean',
    ];

    // ** Relacje

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
    public function animalEdit()
    {
        return $this->belongsTo(\App\Models\AnimalEdit::class, 'animal_edit_id');
    }

}
