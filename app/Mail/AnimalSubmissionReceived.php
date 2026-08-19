<?php

namespace App\Mail;

use App\Models\AnimalEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie przyjęcia nowego zgłoszenia — wysyłane od razu po dodaniu
// ogłoszenia, zanim moderator je zatwierdzi. Bez linku do edycji: Animal
// jeszcze nie istnieje (patrz ModerationService::approve()) — link do edycji
// trafi w osobnym mailu dopiero przy zatwierdzeniu.
//
// Wysyłane synchronicznie (bez ShouldQueue) — nie mamy pewności, że na hostingu
// faktycznie działa worker kolejki, a to pierwszy mail w aplikacji.
// ---------------------------
class AnimalSubmissionReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AnimalEdit $animalEdit)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Otrzymaliśmy Twoje zgłoszenie — Noskiem.pl')
            ->view('emails.animal-submitted');
    }
}
