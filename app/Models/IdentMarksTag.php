<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ---------------------------
// Model reprezentujący słownik podpowiedzi "znaków szczególnych" (np. "Blizna", "Kulawizna").
// Wartości zasilają przyciski szybkiego dodawania w formularzu zgłoszenia —
// pole "ident_marks" na Animal/AnimalEdit jest zwykłym tekstem, nie relacją.
// ---------------------------

class IdentMarksTag extends Model
{
    protected $table = 'ident_marks_tags';

    protected $fillable = [
        'name',
    ];
}
