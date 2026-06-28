<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ProfileController;
use App\Models\AnimalEdit;

// ---------------------------
// STRONA GŁÓWNA
// ---------------------------

Route::get('/', function () {
    return view('welcome');
});

// ---------------------------
// MODERACJA – wymaga zalogowania
// ---------------------------

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $edits = AnimalEdit::where('mod_status', 'pending')
            ->with(['species', 'breed', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('edits'));
    })->middleware('verified')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/moderation/pending', function () {
        $edits = AnimalEdit::where('mod_status', 'pending')
            ->with(['species', 'breed', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('moderation.pending', compact('edits'));
    })->name('moderation.pending');

    Route::get('/moderation/{edit}', function (AnimalEdit $edit) {
        $edit->load(['species', 'breed', 'voivodeship', 'city', 'animal', 'photos']);
        return view('moderation.show', compact('edit'));
    })->whereNumber('edit')->name('moderation.show');

    Route::post('/moderation/{edit}/approve', [ModerationController::class, 'approve'])
        ->whereNumber('edit')
        ->name('moderation.approve');

    Route::post('/moderation/{edit}/reject', [ModerationController::class, 'reject'])
        ->whereNumber('edit')
        ->name('moderation.reject');

    Route::get('/moderation/{edit}/diff', [ModerationController::class, 'diff'])
        ->whereNumber('edit')
        ->name('moderation.diff');
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
