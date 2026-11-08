<?php

namespace App\Mail;

use App\Models\ChipRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Informacja dla właściciela o odrzuceniu wpisu w bazie chipów albo jego edycji
// ($isEdit — wtedy wpis zostaje w bazie w dotychczasowej wersji), z powodem
// (ChipModerationService::reject / rejectChanges)
// ---------------------------
class ChipRegistrationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChipRegistration $registration, public string $reason, public bool $isEdit = false)
    {
    }

    public function build(): self
    {
        $mail = $this
            ->subject(($this->isEdit ? 'Informacja o zmianach we wpisie' : 'Informacja o zgłoszeniu chipa').' — '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org');

        // ** Link do edycji tylko przy odrzuconej edycji — odrzucony wpis nie jest już edytowalny
        if ($this->isEdit) {
            $mail->with(['editUrl' => route('chips.edit', ['chipRegistration' => $this->registration, 'token' => $this->registration->edit_token])]);
        }

        return $mail->view('emails.chip-rejected');
    }
}
