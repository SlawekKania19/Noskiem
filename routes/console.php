<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------------------------
// Zadania cykliczne — wymagają na serwerze wpisu w cronie:
// * * * * * php /ścieżka/do/projektu/artisan schedule:run >> /dev/null 2>&1
// ---------------------------

// ** Comiesięczne przypomnienia o aktywnych ogłoszeniach "Zaginął" (patrz SendLostAnimalReminders)
Schedule::command('animals:send-reminders')
    ->dailyAt('10:00')
    ->withoutOverlapping();

// ** Retencja: ostrzeżenia dla starych ogłoszeń "Znaleziony", usuwanie nieprzedłużonych
// i starych zamkniętych (patrz ApplyAnimalRetention)
Schedule::command('animals:retention')
    ->dailyAt('10:30')
    ->withoutOverlapping();

// ** Dopasowania po zdjęciach: partia wektorów z kolejki (patrz EmbedPhotos).
// Pauza, okno godzin, priorytet i limity — w config/noskiem_match.php (.env).
// Komenda sama sprawdza MATCH_ENABLED, więc wpis jest bezpieczny także bez Pythona.
Schedule::command('match:embed')
    ->everyMinute()
    ->withoutOverlapping(30)
    ->runInBackground();

// ** Maile z podobnymi ogłoszeniami do autorów nowych zgłoszeń (patrz NotifyMatches) —
// czekają, aż match:embed policzy zdjęcia zgłoszenia
Schedule::command('match:notify')
    ->everyMinute()
    ->withoutOverlapping(10);
