<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Message;
use Illuminate\Http\Request;

// ---------------------------
// Kontroler do obsługi wiadomości kontaktowych wysyłanych do właściciela ogłoszenia
// ---------------------------

class MessageController extends Controller
{
    // Zapisuje wiadomość od zainteresowanego i przekierowuje z powrotem do szczegółów ogłoszenia
    public function store(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $data['animal_id'] = $animal->id;

        Message::create($data);

        return redirect()->route('animals.show', $animal)
            ->with('success', 'Wiadomość została wysłana do zgłaszającego.');
    }
}
