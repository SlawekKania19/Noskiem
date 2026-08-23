<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Wiadomość z ogólnego formularza kontaktowego (/kontakt) — wysyłana na stały
// adres zespołu, nie do konkretnego ogłoszenia/zgłoszenia. Reply-To ustawiony
// na adres nadawcy, żeby dało się odpowiedzieć bezpośrednio z klienta poczty.
// ---------------------------
class ContactFormMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $senderName, public string $senderEmail, public string $body)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Nowa wiadomość z formularza kontaktowego — '.config('app.name'))
            ->replyTo($this->senderEmail, $this->senderName)
            ->view('emails.contact-form-message');
    }
}
