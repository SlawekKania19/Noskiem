<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ---------------------------
// Model reprezentujący słownik zachowań zwierzęcia (np. "agresywny", "przyjazny").
// Wartości zasilają przyciski szybkiego dodawania w formularzu zgłoszenia —
// pole "behavior" na Animal/AnimalEdit jest zwykłym tekstem, nie relacją.
// ---------------------------

class Behavior extends Model
{
    protected $fillable = [
        'name',
    ];
}
