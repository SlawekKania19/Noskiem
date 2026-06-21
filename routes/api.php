<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
use App\Http\Controllers\ModerationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Wszystkie endpointy API aplikacji.
| Frontend komunikuje się wyłącznie z tymi adresami.
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:30,1')->group(function () {

    // PUBLICZNE API
    Route::get('/animals', [AnimalController::class, 'index']);
    Route::get('/animals/{animal}', [AnimalController::class, 'show']);

    // TWORZENIE I EDYCJE
    Route::post('/animal-edits', [AnimalEditController::class, 'store']);
    Route::get('/animal-edits/pending', [AnimalEditController::class, 'indexPending']);
    Route::get('/animal-edits/{animalEdit}', [AnimalEditController::class, 'show']);

    // MODERACJA
    Route::post('/moderation/approve/{animalEdit}', [ModerationController::class, 'approve']);
    Route::post('/moderation/reject/{animalEdit}', [ModerationController::class, 'reject']);
    Route::get('/moderation/diff/{animalEdit}', [ModerationController::class, 'diff']);
});

