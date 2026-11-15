<?php

namespace App\Mail\Concerns;

use App\Http\Controllers\UnsubscribeController;
use Illuminate\Mail\Mailables\Headers;

// ---------------------------
// Link "Kliknij, aby zrezygnować z subskrypcji" w mailach do użytkowników:
// - nagłówek List-Unsubscribe (klienci poczty pokazują przy nim przycisk "Zrezygnuj",
//   co zmniejsza szansę oznaczenia maila jako spam),
// - zmienna $unsubscribeUrl dla stopki w emails/layout.blade.php.
// Celowo BEZ List-Unsubscribe-Post (RFC 8058, "one-click") - klient poczty wysłałby wtedy
// POST sam, bez potwierdzenia, a u nas rezygnacja usuwa ogłoszenia. Link prowadzi
// na stronę z listą danych i przyciskiem potwierdzenia.
// Adres odbiorcy bierzemy z Mail::to() - jest już ustawiony, gdy Laravel woła headers().
// Nie dla maili do moderatorów i do nas (formularz kontaktowy).
// ---------------------------
trait HasUnsubscribeLink
{
    public function headers(): Headers
    {
        $url = $this->unsubscribeUrl();

        return new Headers(text: $url ? ['List-Unsubscribe' => '<'.$url.'>'] : []);
    }

    public function buildViewData()
    {
        return [...parent::buildViewData(), 'unsubscribeUrl' => $this->unsubscribeUrl()];
    }

    // ** Jeden link na mail - token jest losowy (szyfrowanie), a nagłówek i stopka
    // powinny wskazywać ten sam adres. Klucz = adres, gdyby ten sam obiekt wysłano do kogoś innego
    private array $unsubscribeUrls = [];

    private function unsubscribeUrl(): ?string
    {
        $email = $this->to[0]['address'] ?? null;

        if (! $email) {
            return null;
        }

        return $this->unsubscribeUrls[$email] ??= UnsubscribeController::urlFor($email);
    }
}
