<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// ---------------------------
// Statyczna strona kontaktowa (/kontakt) — formularz wysyłający wiadomość na
// stały adres zespołu (nie do konkretnego ogłoszenia). Treść nad formularzem
// edytowalna z panelu Ustawień (App\Filament\Pages\Settings), Markdown -> HTML.
// ---------------------------
class ContactController extends Controller
{
    private const RECIPIENT = 'kontakt@noskiem.org';

    public function show()
    {
        $intro = Setting::get(
            'contact_page_intro',
            'Masz pytanie, sugestię albo chcesz nawiązać współpracę? Napisz do nas — odpowiadamy najszybciej jak to możliwe.'
        );

        return view('contact.show', [
            'introHtml' => Str::markdown((string) $intro),
        ]);
    }

    public function store(Request $request)
    {
        // ** Honeypot — pole niewidoczne dla ludzi, ale boty je wypełniają. Udajemy sukces,
        // żeby bot nie wiedział, że został złapany, i nie próbował omijać zabezpieczenia
        if ($request->filled('website')) {
            return redirect()->route('contact.show')
                ->with('success', 'Wiadomość została wysłana. Odpowiemy najszybciej jak to możliwe.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000',
        ]);

        try {
            Mail::to(self::RECIPIENT)
                ->send(new ContactFormMessage($data['name'], $data['email'], $data['message']));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('contact.show')
            ->with('success', 'Wiadomość została wysłana. Odpowiemy najszybciej jak to możliwe.');
    }
}
