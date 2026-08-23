<?php

namespace App\Mail;

use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie przyjęcia zgłoszenia "też widziałem" — wysyłane od razu po
// dodaniu, zanim moderator je zobaczy. Zawiera link do potwierdzenia adresu
// e-mail — dopóki go nie klikniesz, zgłoszenie nie trafi do moderatorów (patrz
// SightingController::confirmEmail), tak samo jak przy zgłoszeniu ogłoszenia.
// ---------------------------
class SightingSubmissionReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sighting $sighting)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Potwierdź adres e-mail — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'confirmUrl' => route('sightings.confirm', ['sighting' => $this->sighting, 'token' => $this->sighting->edit_token]),
                'animalUrl' => route('animals.show', $this->sighting->animal_id),
            ])
            ->view('emails.sighting-submitted');
    }
}
