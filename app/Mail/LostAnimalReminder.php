<?php

namespace App\Mail;

use App\Models\Animal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Comiesięczne przypomnienie dla właściciela ogłoszenia o zaginionym zwierzaku —
// wysyłane przez komendę animals:send-reminders. Ma skłonić do oznaczenia ogłoszenia
// jako "Znaleziony" lub usunięcia go, gdy zwierzak już się odnalazł.
// ---------------------------
class LostAnimalReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Animal $animal)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Wciąż szukamy Twojego zwierzaka — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'editUrl' => route('animals.edit', ['animal' => $this->animal, 'token' => $this->animal->edit_token]),
                // ** Jak w AnimalSubmissionApproved — ?print=1 otwiera od razu modal plakatu
                'posterUrl' => route('animals.show', $this->animal).'?print=1',
            ])
            ->view('emails.lost-animal-reminder');
    }
}
