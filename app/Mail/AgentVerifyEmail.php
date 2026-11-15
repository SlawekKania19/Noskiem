<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Potwierdzenie adresu e-mail po rejestracji Agenta noskiem (link podpisany, 7 dni).
// Dopiero po potwierdzeniu agent dostaje prośby o pomoc i może klikać "Szukam".
// ---------------------------
class AgentVerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $agent)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Potwierdź adres e-mail - Agent noskiem')
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->view('emails.agent-verify', ['url' => $this->agent->agentVerificationUrl()]);
    }
}
