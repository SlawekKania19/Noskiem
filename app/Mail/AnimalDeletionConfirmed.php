<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie trwałego usunięcia ogłoszenia (przez samego zgłaszającego,
// AnimalEditController::destroySelf()). Celowo przyjmuje zwykłe skalary, nie model
// Animal — w momencie wysyłki rekord już nie istnieje w bazie, więc przekazywanie
// modelu byłoby mylące (i niebezpieczne, gdyby to kiedyś zostało zqueue'owane —
// SerializesModels próbowałby doładować z bazy nieistniejący już rekord).
// ---------------------------
class AnimalDeletionConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public int $animalId,
        public string $contactName,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Ogłoszenie zostało usunięte — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->view('emails.animal-deleted');
    }
}
