<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
use App\Http\Controllers\ProfileController;

// ---------------------------
// STRONA GŁÓWNA
// ---------------------------

Route::get('/', function () {
    return view('welcome');
});

// ---------------------------
// AUTH – panel admina i profil
// ---------------------------

Route::middleware('auth')->group(function () {

    // Po zalogowaniu Breeze przekierowuje tu — przenosimy od razu do Filament
    Route::get('/dashboard', function () {
        return redirect('/admin');
    })->middleware('verified')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------------------------
// PUBLICZNE – tylko odczyt
// ---------------------------

Route::get('/animals', [AnimalController::class, 'index'])
    ->name('animals.index');

// ---------------------------
// TWORZENIE I EDYCJA – trafia do animal_edits
// Trasa /animals/create MUSI być przed /animals/{animal}
// ---------------------------

Route::get('/animals/create', [AnimalEditController::class, 'create'])
    ->name('animals.create');

Route::post('/animals', [AnimalEditController::class, 'store'])
    ->name('animals.store');

Route::get('/animals/{animal}', [AnimalController::class, 'show'])
    ->name('animals.show');

Route::get('/animals/{animal}/edit', [AnimalEditController::class, 'edit'])
    ->name('animals.edit');

Route::post('/animals/{animal}/edit', [AnimalEditController::class, 'update'])
    ->name('animals.update');

// ---------------------------
// BREEZE – trasy logowania/rejestracji
// ---------------------------

require __DIR__.'/auth.php';
