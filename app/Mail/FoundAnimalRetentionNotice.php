<?php

namespace App\Mail;

use App\Models\Animal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

// ---------------------------
// Ostrzeżenie dla autora ogłoszenia "Znaleziony", że ogłoszenie jest stare i zostanie
// usunięte — wysyłane przez komendę animals:retention. Link prowadzi na stronę edycji,
// gdzie baner pozwala przedłużyć ogłoszenie o 6 miesięcy (POST, nie GET — skanery
// linków w poczcie nie mogą przedłużyć ogłoszenia za autora).
// ---------------------------
class FoundAnimalRetentionNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Animal $animal, public Carbon $deleteAt)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Twoje ogłoszenie wkrótce zniknie — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'extendUrl' => route('animals.edit', ['animal' => $this->animal, 'token' => $this->animal->edit_token]).'#extend',
                // Pierwsze ostrzeżenie po EXPIRE_YEARS, kolejne po przedłużeniu — inna treść wstępu
                'wasExtended' => $this->animal->retention_until !== null,
            ])
            ->view('emails.found-animal-retention-notice');
    }
}
