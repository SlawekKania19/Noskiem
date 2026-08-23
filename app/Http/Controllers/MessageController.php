<?php

namespace App\Http\Controllers;

use App\Mail\NewContactMessage;
use App\Models\Animal;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

// ---------------------------
// Kontroler do obsługi wiadomości kontaktowych wysyłanych do właściciela ogłoszenia
// ---------------------------

class MessageController extends Controller
{
    // Zapisuje wiadomość od zainteresowanego, wysyła powiadomienie e-mail do
    // autora ogłoszenia i przekierowuje z powrotem do szczegółów ogłoszenia
    public function store(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000',
            'accept_terms' => 'accepted',
        ]);

        unset($data['accept_terms']);

        $data['animal_id'] = $animal->id;
        $data['submitter_ip'] = $request->ip();

        $message = Message::create($data);

        // ** Brak maila do autora nie powinien blokować zapisania wiadomości —
        // ona i tak zostaje widoczna w panelu admina
        if ($animal->contact_email) {
            try {
                Mail::to($animal->contact_email, $animal->contact_name)
                    ->send(new NewContactMessage($message, $animal));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('animals.show', $animal)
            ->with('success', 'Wiadomość została wysłana do zgłaszającego.');
    }
}
