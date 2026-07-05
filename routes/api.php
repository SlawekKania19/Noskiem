<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Wszystkie endpointy API aplikacji.
| Moderacja odbywa się wyłącznie przez panel Filament (/admin).
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:4,1')->group(function () {

    // PUBLICZNE API
    Route::get('/animals', [AnimalController::class, 'index']);
    Route::get('/animals/{animal}', [AnimalController::class, 'show']);

    // TWORZENIE I EDYCJE
    Route::post('/animal-edits', [AnimalEditController::class, 'store']);
    Route::get('/animal-edits/pending', [AnimalEditController::class, 'indexPending']);
    Route::get('/animal-edits/{animalEdit}', [AnimalEditController::class, 'show']);
});

