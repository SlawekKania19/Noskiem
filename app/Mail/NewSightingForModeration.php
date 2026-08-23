<?php

namespace App\Mail;

use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Powiadomienie dla moderatorów/adminów o nowym zgłoszeniu "też widziałem"
// czekającym na moderację. Wysyłane osobno do każdego uprawnionego (patrz
// SightingController::confirmEmail), tak samo jak NewSubmissionForModeration.
// ---------------------------
class NewSightingForModeration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sighting $sighting)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Nowe zgłoszenie "też widziałem" czeka na moderację — '.config('app.name'))
            ->with(['moderationUrl' => route('filament.admin.resources.sightings.view', $this->sighting)])
            ->view('emails.sighting-moderation-pending');
    }
}
