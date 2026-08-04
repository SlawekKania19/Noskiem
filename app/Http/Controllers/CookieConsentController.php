<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// ---------------------------
// Obsługa banera zgody na ciasteczka — zgoda trzymana w sesji (nie w osobnym
// ciasteczku), więc obowiązuje tylko do końca bieżącej sesji przeglądarki.
// "level" to "all" (wszystkie) albo "necessary" (tylko niezbędne) — na razie
// nie mamy żadnych opcjonalnych ciasteczek, więc obie wartości dają ten sam
// efekt praktyczny, ale rozróżnienie jest gotowe pod przyszłe kategorie.
// ---------------------------

class CookieConsentController extends Controller
{
    public function accept(Request $request)
    {
        $level = $request->validate([
            'level' => ['required', 'in:all,necessary'],
        ])['level'];

        $request->session()->put('cookie_consent', $level);

        return back();
    }
}
