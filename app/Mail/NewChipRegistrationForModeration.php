<?php

namespace App\Mail;

use App\Models\ChipRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Powiadomienie dla moderatorów/adminów o nowym zgłoszeniu do bazy chipów.
// Wysyłane osobno do każdego (patrz ChipRegistrationController::confirmEmail).
// ---------------------------
class NewChipRegistrationForModeration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChipRegistration $registration)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Nowe zgłoszenie do bazy chipów czeka na moderację - '.config('app.name'))
            ->with(['moderationUrl' => route('filament.admin.resources.chip-registrations.view', $this->registration)])
            ->view('emails.chip-moderation-pending');
    }
}
