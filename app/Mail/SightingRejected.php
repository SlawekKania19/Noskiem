<?php

namespace App\Mail;

use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Informacja o odrzuceniu zgłoszenia "też widziałem" — wysyłane z
// SightingModerationService::reject().
// ---------------------------
class SightingRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sighting $sighting)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Informacja o Twoim zgłoszeniu — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->view('emails.sighting-rejected');
    }
}
