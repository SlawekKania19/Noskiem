<?php

namespace App\Http\Controllers;

use App\Services\EmailDataEraser;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

// ---------------------------
// Rezygnacja z subskrypcji (link w stopce maili, patrz Mail\Concerns\HasUnsubscribeLink).
// U nas oznacza bezpowrotne usunięcie wszystkich danych powiązanych z adresem e-mail
// (EmailDataEraser), więc:
// - GET tylko pokazuje listę ogłoszeń/zgłoszeń/chipów i ostrzeżenie,
// - usunięcie dopiero po POST z zaznaczonym potwierdzeniem - skanery poczty same
//   otwierają linki z maili i bez tego kasowałyby dane za użytkownika.
// Adres w linku jest zaszyfrowany (Crypt, z podpisem MAC) - nie widać go w adresie strony
// ani w logach serwera, a podmiana na cudzy adres nie przejdzie deszyfrowania.
// Link bez wygasania - musi działać także w starych mailach.
// ---------------------------
class UnsubscribeController extends Controller
{
    public function __construct(private EmailDataEraser $eraser)
    {
    }

    // ** Link do strony rezygnacji dla adresu (używany przy budowie maili)
    public static function urlFor(string $email): string
    {
        // ** Crypt zwraca base64 z "/", "+" i "=" - w segmencie ścieżki zamieniamy na wariant URL-safe
        $token = rtrim(strtr(Crypt::encryptString(mb_strtolower(trim($email))), '+/', '-_'), '=');

        return route('unsubscribe.show', ['token' => $token]);
    }

    public function show(string $token)
    {
        $email = $this->decrypt($token);
        $data = $this->eraser->collect($email);

        return view('unsubscribe.show', [
            'email'   => $email,
            'data'    => $data,
            'isEmpty' => $this->eraser->isEmpty($data),
            'deleted' => null,
        ]);
    }

    public function destroy(Request $request, string $token)
    {
        $email = $this->decrypt($token);

        $request->validate(
            ['confirm' => 'accepted'],
            ['confirm.accepted' => 'Zaznacz, że rozumiesz, że usunięcie jest bezpowrotne.'],
        );

        $deleted = $this->eraser->erase($email);

        return view('unsubscribe.show', [
            'email'   => $email,
            'data'    => null,
            'isEmpty' => true,
            'deleted' => $deleted,
        ]);
    }

    // ** Uszkodzony/podmieniony token -> 404 (bez zdradzania, czy adres istnieje)
    private function decrypt(string $token): string
    {
        try {
            return Crypt::decryptString(strtr($token, '-_', '+/'));
        } catch (DecryptException) {
            abort(404);
        }
    }
}
