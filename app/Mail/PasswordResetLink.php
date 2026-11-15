<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Link do ustawienia nowego hasła - polska wersja w naszym szablonie zamiast domyślnego,
// angielskiego maila Laravela (podpięte w AppServiceProvider::registerPasswordResetMail).
// ---------------------------
class PasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $url, public int $minutes)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Zmiana hasła - '.config('app.name'))
            ->view('emails.password-reset');
    }
}
