<?php

namespace App\Mail;

use App\Models\Animal;
use App\Models\Message;
use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Powiadomienie o nowej wiadomości od zainteresowanego — albo dla autora
// ogłoszenia (formularz kontaktowy w szczegółach ogłoszenia), albo dla autora
// konkretnego zgłoszenia "też widziałem" (przycisk "Kontakt z autorem" w
// timeline, $sighting wtedy podane). Reply-To ustawiony na adres nadawcy
// wiadomości, żeby odbiorca mógł odpowiedzieć bezpośrednio z klienta poczty.
// ---------------------------
class NewContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    // ** Nazwa właściwości NIE może być $message — to zarezerwowana nazwa w widokach
    // maili (Illuminate\Mail\Message wstrzykiwane automatycznie), więc kolizja
    // wywala renderowanie widoku
    public function __construct(public Message $contactMessage, public Animal $animal, public ?Sighting $sighting = null)
    {
    }

    public function build(): self
    {
        $subject = $this->sighting
            ? 'Nowa wiadomość w sprawie Twojego zgłoszenia — '.config('app.name')
            : 'Nowa wiadomość w sprawie ogłoszenia — '.config('app.name');

        return $this
            ->subject($subject)
            ->replyTo($this->contactMessage->email, $this->contactMessage->name)
            ->with([
                'animalUrl' => route('animals.show', $this->animal),
                'isSighting' => $this->sighting !== null,
            ])
            ->view('emails.new-message');
    }
}
