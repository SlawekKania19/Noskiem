<?php

namespace App\Mail;

use App\Models\AnimalEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Informacja o odrzuceniu — wysyłane z ModerationService::reject(). Treść
// rozróżnia dwa przypadki (animal_id): odrzucenie zupełnie nowego zgłoszenia
// vs. odrzucenie edycji już opublikowanego, istniejącego ogłoszenia (które
// w tym drugim przypadku nadal wisi na stronie bez zmian).
// ---------------------------
class AnimalSubmissionRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AnimalEdit $animalEdit)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Informacja o Twoim zgłoszeniu — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with(['isEdit' => $this->animalEdit->animal_id !== null])
            ->view('emails.animal-rejected');
    }
}
