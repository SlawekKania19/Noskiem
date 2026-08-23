<?php

namespace App\Http\Controllers;

use App\Mail\NewContactMessage;
use App\Models\Animal;
use App\Models\Message;
use App\Models\Sighting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

// ---------------------------
// Kontroler do obsługi wiadomości kontaktowych — do autora ogłoszenia albo,
// przez "Kontakt z autorem" w timeline, do autora konkretnego zgłoszenia
// "też widziałem" (patrz storeForSighting).
// ---------------------------

class MessageController extends Controller
{
    // Zapisuje wiadomość do autora ogłoszenia, wysyła powiadomienie e-mail
    // i przekierowuje z powrotem do szczegółów ogłoszenia
    public function store(Request $request, Animal $animal)
    {
        if ($this->isHoneypotTriggered($request)) {
            return $this->successRedirect($animal);
        }

        $data = $this->validated($request);
        $data['animal_id'] = $animal->id;
        $data['submitter_ip'] = $request->ip();

        $message = Message::create($data);

        $this->notify($message, $animal, $animal->contact_email, $animal->contact_name);

        return $this->successRedirect($animal);
    }

    // Zapisuje wiadomość do autora zgłoszenia "też widziałem" (nie do autora
    // ogłoszenia) — ten sam formularz, inny odbiorca
    public function storeForSighting(Request $request, Sighting $sighting)
    {
        if ($this->isHoneypotTriggered($request)) {
            return $this->successRedirect($sighting->animal_id);
        }

        $data = $this->validated($request);
        $data['animal_id'] = $sighting->animal_id;
        $data['sighting_id'] = $sighting->id;
        $data['submitter_ip'] = $request->ip();

        $message = Message::create($data);

        $this->notify($message, $sighting->animal, $sighting->contact_email, $sighting->contact_name, $sighting);

        return $this->successRedirect($sighting->animal_id);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000',
            'accept_terms' => 'accepted',
        ]);

        unset($data['accept_terms']);

        return $data;
    }

    // ** Honeypot — pole niewidoczne dla ludzi, ale boty je wypełniają
    private function isHoneypotTriggered(Request $request): bool
    {
        return $request->filled('website');
    }

    // ** Brak maila do odbiorcy nie powinien blokować zapisania wiadomości —
    // ona i tak zostaje widoczna w panelu admina
    private function notify(Message $message, Animal $animal, ?string $recipientEmail, ?string $recipientName, ?Sighting $sighting = null): void
    {
        if (! $recipientEmail) {
            return;
        }

        try {
            Mail::to($recipientEmail, $recipientName)
                ->send(new NewContactMessage($message, $animal, $sighting));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function successRedirect(Animal|int $animal)
    {
        return redirect()->route('animals.show', $animal)
            ->with('success', 'Wiadomość została wysłana do zgłaszającego.');
    }
}
