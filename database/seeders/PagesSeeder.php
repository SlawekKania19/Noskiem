<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate — bezpieczne przy wielokrotnym odpaleniu seedera
        Page::updateOrCreate(['slug' => 'cookies'], [
            'title' => 'Informacje o ciasteczkach',
            'content' => <<<'MARKDOWN'
            Noskiem.pl używa wyłącznie ciasteczek niezbędnych do prawidłowego działania strony. Nie korzystamy z ciasteczek analitycznych, marketingowych ani reklamowych.

            ## Jakich ciasteczek używamy

            | Nazwa | Cel | Czas przechowywania |
            |---|---|---|
            | `_session_id` | Utrzymanie sesji użytkownika (m.in. zapamiętanie zgody wyrażonej w banerze) | Do zamknięcia przeglądarki / zakończenia sesji |
            | `XSRF-TOKEN` | Ochrona formularzy przed atakami typu CSRF | Do zamknięcia przeglądarki / zakończenia sesji |

            Obie wartości są niezbędne do działania strony (m.in. formularza zgłoszenia i moderacji) i nie wymagają osobnej zgody.

            ## Zarządzanie zgodą

            Zgoda wyrażona w banerze na dole strony obowiązuje do końca bieżącej sesji przeglądarki — po jej zakończeniu baner pojawi się ponownie.

            ## Kontakt

            W razie pytań dotyczących przetwarzania danych i ciasteczek napisz na adres kontaktowy podany w stopce strony.
            MARKDOWN,
        ]);
    }
}
