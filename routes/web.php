<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalEditController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SightingController;

// ---------------------------
// STRONA GŁÓWNA
// ---------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');

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

// ** Mapa wszystkich zatwierdzonych ogłoszeń z pinezkami (filtry po stronie przeglądarki)
Route::get('/map', [AnimalController::class, 'map'])
    ->name('map.index');

// ** Autouzupełnianie miejscowości w formularzach (min. 3 znaki, filtrowane po województwie)
Route::get('/cities/search', [CityController::class, 'search'])
    ->middleware('throttle:60,1')
    ->name('cities.search');

// ** Odwrotne geokodowanie pinezki z mapy (lat/lng -> województwo/miejscowość)
Route::get('/location/reverse', [LocationController::class, 'reverse'])
    ->middleware('throttle:30,1')
    ->name('location.reverse');

// ---------------------------
// TWORZENIE I EDYCJA – trafia do animal_edits
// Trasa /animals/create MUSI być przed /animals/{animal}
// ---------------------------

Route::get('/animals/create', [AnimalEditController::class, 'create'])
    ->name('animals.create');

// ** Limit (liczba/10 min) edytowalny w panelu Ustawień — patrz AppServiceProvider::registerRateLimiters()
Route::post('/animals', [AnimalEditController::class, 'store'])
    ->middleware('throttle:animals-store')
    ->name('animals.store');

Route::get('/animals/{animal}', [AnimalController::class, 'show'])
    ->name('animals.show');

// ** Limit (liczba/10 min) edytowalny w panelu Ustawień — patrz AppServiceProvider::registerRateLimiters()
Route::post('/animals/{animal}/messages', [MessageController::class, 'store'])
    ->middleware('throttle:messages-store')
    ->name('messages.store');

// ** Zgłoszenie "też widziałem" pod ogłoszeniem (tylko status "found", patrz SightingController)
Route::get('/animals/{animal}/sightings/create', [SightingController::class, 'create'])
    ->name('sightings.create');

Route::post('/animals/{animal}/sightings', [SightingController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('sightings.store');

Route::get('/sightings/{sighting}/confirm', [SightingController::class, 'confirmEmail'])
    ->name('sightings.confirm');

// ** "Kontakt z autorem" w timeline — wiadomość do autora konkretnego zgłoszenia, nie ogłoszenia
Route::post('/sightings/{sighting}/messages', [MessageController::class, 'storeForSighting'])
    ->middleware('throttle:messages-store')
    ->name('sightings.messages.store');

Route::get('/animals/{animal}/edit', [AnimalEditController::class, 'edit'])
    ->name('animals.edit');

Route::post('/animals/{animal}/edit', [AnimalEditController::class, 'update'])
    ->name('animals.update');

// ** Bez moderacji — bezpośrednio przez zgłaszającego (token), zgodnie z zasadą,
// że zmiana statusu / usunięcie własnego ogłoszenia nie wymaga zatwierdzenia
Route::post('/animals/{animal}/resolve', [AnimalEditController::class, 'markResolved'])
    ->name('animals.resolve');

Route::post('/animals/{animal}/delete', [AnimalEditController::class, 'destroySelf'])
    ->name('animals.selfDelete');

// ** Potwierdzenie adresu e-mail nowego zgłoszenia — dopiero po tym moderatorzy
// dostają powiadomienie (patrz AnimalEditController::confirmEmail)
Route::get('/animal-edits/{animalEdit}/confirm', [AnimalEditController::class, 'confirmEmail'])
    ->name('animal-edits.confirm');

// ---------------------------
// ZGODA NA CIASTECZKA — zapisywana w sesji, patrz CookieConsentController
// ---------------------------

Route::post('/cookies/accept', [CookieConsentController::class, 'accept'])
    ->name('cookies.accept');

// ---------------------------
// KONTAKT — treść nad formularzem edytowalna z panelu Ustawień (Markdown)
// ---------------------------

Route::get('/kontakt', [ContactController::class, 'show'])
    ->name('contact.show');

Route::post('/kontakt', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');

// ---------------------------
// BREEZE – trasy logowania/rejestracji
// ---------------------------

require __DIR__.'/auth.php';

// ---------------------------
// STATYCZNE PODSTRONY (np. /cookies) — treść z panelu Filament (App\Filament\Resources\PageResource)
// MUSI być ostatnia trasa w pliku, żeby "catch-all" po slugu nie przechwytywał innych adresów
// ---------------------------

Route::get('/{page:slug}', [PageController::class, 'show'])
    ->name('pages.show');
