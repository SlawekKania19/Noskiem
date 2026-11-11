<?php

namespace App\Mail;

use App\Models\ChipRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Informacja dla właściciela, że wpis w bazie chipów (albo jego edycja - $isEdit)
// został zatwierdzony. Zawiera link do edycji/usunięcia wpisu z tokenem.
// ---------------------------
class ChipRegistrationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChipRegistration $registration, public bool $isEdit = false)
    {
    }

    public function build(): self
    {
        return $this
            ->subject(($this->isEdit ? 'Zmiany we wpisie zatwierdzone' : 'Chip zarejestrowany w bazie').' - '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'searchUrl' => route('chips.index'),
                'editUrl' => route('chips.edit', ['chipRegistration' => $this->registration, 'token' => $this->registration->edit_token]),
            ])
            ->view('emails.chip-approved');
    }
}
