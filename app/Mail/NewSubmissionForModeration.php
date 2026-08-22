<?php

namespace App\Mail;

use App\Models\AnimalEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Powiadomienie dla moderatorów/adminów o nowym zgłoszeniu czekającym na
// moderację. Wysyłane osobno do każdego uprawnionego (patrz
// AnimalEditController::store() — pętla po User::gdzie is_admin lub is_moderator,
// ta sama reguła dostępu co w AnimalEditResource::canViewAny()).
// ---------------------------
class NewSubmissionForModeration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AnimalEdit $animalEdit)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Nowe zgłoszenie czeka na moderację — '.config('app.name'))
            ->with(['moderationUrl' => route('filament.admin.resources.animal-edits.view', $this->animalEdit)])
            ->view('emails.moderation-pending');
    }
}
