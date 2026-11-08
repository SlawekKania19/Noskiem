<?php

namespace App\Mail;

use App\Models\ChipRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie przyjęcia zgłoszenia do bazy chipów — wysyłane od razu po
// dodaniu. Zawiera link do potwierdzenia adresu e-mail — dopóki nie zostanie
// kliknięty, zgłoszenie nie trafi do moderatorów (ChipRegistrationController::confirmEmail).
// ---------------------------
class ChipRegistrationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChipRegistration $registration)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Potwierdź adres e-mail — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'confirmUrl' => route('chips.confirm', ['chipRegistration' => $this->registration, 'token' => $this->registration->edit_token]),
            ])
            ->view('emails.chip-submitted');
    }
}
