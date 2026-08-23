<?php

namespace App\Mail;

use App\Models\Animal;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Powiadomienie dla autora ogłoszenia o nowej wiadomości od zainteresowanego
// (formularz kontaktowy w szczegółach ogłoszenia). Reply-To ustawiony na adres
// nadawcy wiadomości, żeby autor mógł odpowiedzieć bezpośrednio z klienta poczty.
// ---------------------------
class NewContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    // ** Nazwa właściwości NIE może być $message — to zarezerwowana nazwa w widokach
    // maili (Illuminate\Mail\Message wstrzykiwane automatycznie), więc kolizja
    // wywala renderowanie widoku
    public function __construct(public Message $contactMessage, public Animal $animal)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Nowa wiadomość w sprawie ogłoszenia — '.config('app.name'))
            ->replyTo($this->contactMessage->email, $this->contactMessage->name)
            ->with(['animalUrl' => route('animals.show', $this->animal)])
            ->view('emails.new-message');
    }
}
