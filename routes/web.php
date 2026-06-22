<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
use App\Http\Controllers\ModerationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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


// ---------------------------
// MODERACJA – zatwierdzanie zmian
// ---------------------------

Route::post('/moderation/{edit}/approve', [ModerationController::class, 'approve'])
    ->name('moderation.approve');

Route::post('/moderation/{edit}/reject', [ModerationController::class, 'reject'])
    ->name('moderation.reject');
