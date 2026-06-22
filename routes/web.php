<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
use App\Http\Controllers\ModerationController;
use App\Models\AnimalEdit;

/*
|--------------------------------------------------------------------------
| Lista Routingów WEB aplikacji
| Wszystkie endpointy dostępne w przeglądarce.
|--------------------------------------------------------------------------
*/

// ---------------------------
// MODERACJA 
// ---------------------------

// ** Wyświetlenie listy zgłoszeń oczekujących na moderację

Route::get('/moderation/pending', function () {
    $edits = AnimalEdit::where('mod_status', 'pending')
        ->with(['species', 'breed', 'city', 'photos'])
        ->orderBy('created_at', 'desc')
        ->get();

    return view('moderation.pending', compact('edits'));
    })
    ->name('moderation.pending');

// ** Wyświetlenie szczegółów zgłoszenia do moderacji

Route::get('/moderation/{edit}', function (AnimalEdit $edit) {
    $edit->load(['species', 'breed', 'voivodeship', 'city', 'animal', 'photos']);
    return view('moderation.show', compact('edit'));
    })
    ->whereNumber('edit') // Upewniamy się, że {edit} jest liczbą (ID)
    ->name('moderation.show');

Route::post('/moderation/{edit}/approve', [ModerationController::class, 'approve'])
    ->name('moderation.approve');

Route::post('/moderation/{edit}/reject', [ModerationController::class, 'reject'])
    ->name('moderation.reject');

Route::get('/moderation/{edit}/diff', [ModerationController::class, 'diff'])
    ->name('moderation.diff');


// ---------------------------
// PUBLICZNE – tylko odczyt
// ---------------------------

Route::get('/', function () {
    return view('welcome');
});

Route::get('/animals', [AnimalController::class, 'index'])
    ->name('animals.index');

Route::get('/animals/{animal}', [AnimalController::class, 'show'])
    ->name('animals.show');


// ---------------------------
// TWORZENIE I EDYCJA – trafia do animal_edits
// ---------------------------

Route::get('/animals/create', [AnimalEditController::class, 'create'])
    ->name('animals.create');

Route::post('/animals', [AnimalEditController::class, 'store'])
    ->name('animals.store');

Route::get('/animals/{animal}/edit', [AnimalEditController::class, 'edit'])
    ->name('animals.edit');

Route::post('/animals/{animal}/edit', [AnimalEditController::class, 'update'])
    ->name('animals.update');




