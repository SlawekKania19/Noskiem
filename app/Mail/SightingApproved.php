<?php

namespace App\Mail;

use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie zatwierdzenia zgłoszenia "też widziałem" — wysyłane z
// SightingModerationService::approve(). Link prowadzi do ogłoszenia, pod
// którym zgłoszenie jest teraz widoczne jako wpis w timeline.
// ---------------------------
class SightingApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sighting $sighting)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Twoje zgłoszenie zostało opublikowane — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with(['animalUrl' => route('animals.show', $this->sighting->animal_id)])
            ->view('emails.sighting-approved');
    }
}
