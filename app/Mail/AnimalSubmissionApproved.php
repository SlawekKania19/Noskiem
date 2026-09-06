<?php

namespace App\Mail;

use App\Models\Animal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie zatwierdzenia — wysyłane z ModerationService::approve(), gdy
// Animal już istnieje i ma prawdziwy edit_token. Link prowadzi na stronę edycji,
// która ma teraz też przyciski "Znaleziono zwierzaka" i "Usuń ogłoszenie".
// ---------------------------
class AnimalSubmissionApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Animal $animal)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Twoje ogłoszenie zostało zatwierdzone — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'editUrl' => route('animals.edit', ['animal' => $this->animal, 'token' => $this->animal->edit_token]),
                // ** Link do strony ogłoszenia z ?print=1 — otwiera od razu modal wyboru
                // formatu plakatu (patrz resources/views/animals/show.blade.php)
                'posterUrl' => route('animals.show', $this->animal).'?print=1',
            ])
            ->view('emails.animal-approved');
    }
}
