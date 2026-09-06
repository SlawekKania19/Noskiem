<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// ---------------------------
// Przykładowe kategorie i wpisy bloga. updateOrCreate po slugu — bezpieczne przy
// wielokrotnym odpaleniu. Autorem jest konto "Redakcja Noskiem" (rola is_author).
// ---------------------------

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // ** Autor wpisów
        $author = User::updateOrCreate(
            ['email' => 'redakcja@noskiem.org'],
            [
                'name' => 'Redakcja Noskiem',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_author' => true,
            ],
        );

        // ** Kategorie
        $categories = collect([
            ['name' => 'Porady', 'slug' => 'porady', 'sort' => 10,
                'description' => 'Praktyczne wskazówki na co dzień i w sytuacjach awaryjnych.'],
            ['name' => 'Zdrowie i profilaktyka', 'slug' => 'zdrowie', 'sort' => 20,
                'description' => 'Chipowanie, szczepienia, bezpieczeństwo zwierzaka.'],
            ['name' => 'Adopcja i pierwsze dni', 'slug' => 'adopcja', 'sort' => 30,
                'description' => 'Jak przygotować dom i pomóc zwierzakowi się zaaklimatyzować.'],
        ])->mapWithKeys(fn (array $c) => [
            $c['slug'] => BlogCategory::updateOrCreate(['slug' => $c['slug']], $c),
        ]);

        // ** Wpisy
        $posts = [
            [
                'slug' => 'zaginal-pies-pierwsze-24-godziny',
                'category' => 'porady',
                'title' => 'Zaginął pies — pierwsze 24 godziny. Co robić krok po kroku',
                'excerpt' => 'Pierwsza doba jest najważniejsza. Zebraliśmy listę działań, które realnie zwiększają szansę na szybki powrót zwierzaka.',
                'body' => <<<'HTML'
                <p>Kiedy pies znika, liczy się każda minuta. Zamiast panikować, działaj według planu.</p>
                <h2>Zaraz po zniknięciu</h2>
                <ul>
                    <li>Przeszukaj najbliższą okolicę, wołając psa spokojnym, znajomym głosem.</li>
                    <li>Zostaw przy domu jego posłanie i miskę z wodą — zapach pomaga wrócić.</li>
                    <li>Poproś domowników i sąsiadów o pomoc w przeszukaniu podwórek i klatek.</li>
                </ul>
                <h2>W ciągu kilku godzin</h2>
                <ul>
                    <li>Dodaj ogłoszenie w serwisie Noskiem i w lokalnych grupach.</li>
                    <li>Zadzwoń do najbliższego schroniska i lecznic weterynaryjnych.</li>
                    <li>Jeśli pies jest zaczipowany — upewnij się, że dane w bazie są aktualne.</li>
                </ul>
                <h2>Czego nie robić</h2>
                <p>Nie biegnij za przestraszonym psem — najczęściej ucieka wtedy dalej. Kucnij, unikaj kontaktu wzrokowego i poczekaj, aż sam podejdzie.</p>
                HTML,
            ],
            [
                'slug' => 'jak-zabezpieczyc-dom-zeby-kot-nie-uciekl',
                'category' => 'porady',
                'title' => 'Jak zabezpieczyć dom i ogród, żeby kot nie uciekł',
                'excerpt' => 'Większości ucieczek da się zapobiec. Przegląd rozwiązań — od siatek okiennych po zabezpieczenie ogrodu.',
                'body' => <<<'HTML'
                <p>Kot rzadko ucieka „dla przygody” — zwykle wypycha go strach albo instynkt. Dobre zabezpieczenia rozwiązują problem u źródła.</p>
                <h2>Okna i balkon</h2>
                <ul>
                    <li>Siatki „dla kota” na ramach okiennych i na balkonie.</li>
                    <li>Blokady uchylnych okien — szczelina potrafi uwięzić zwierzaka.</li>
                </ul>
                <h2>Ogród</h2>
                <ul>
                    <li>Nawisy skierowane do wewnątrz na górze ogrodzenia.</li>
                    <li>Kontrola bram i furtek — najczęstsza droga ucieczki.</li>
                </ul>
                <h2>Na wszelki wypadek</h2>
                <p>Zaczipuj kota i zarejestruj chip w bazie. Adresatka na obroży z numerem telefonu skraca czas powrotu do kilku godzin.</p>
                HTML,
            ],
            [
                'slug' => 'chip-i-rejestracja-dlaczego-dziala',
                'category' => 'zdrowie',
                'title' => 'Chip i jego rejestracja — dlaczego to naprawdę działa',
                'excerpt' => 'Sam chip to za mało. Wyjaśniamy, jak działa rejestracja i dlaczego bez niej mikroczip bywa bezużyteczny.',
                'body' => <<<'HTML'
                <p>Mikrochip to ziarno ryżu pod skórą z unikalnym numerem. Ale numer bez wpisu w bazie nie prowadzi do nikogo.</p>
                <h2>Jak to działa</h2>
                <ol>
                    <li>Weterynarz lub schronisko skanuje chip i odczytuje numer.</li>
                    <li>Numer sprawdzany jest w bazie identyfikacji zwierząt.</li>
                    <li>Baza zwraca dane kontaktowe opiekuna.</li>
                </ol>
                <h2>Najczęstszy błąd</h2>
                <p>Zmiana numeru telefonu albo adresu bez aktualizacji wpisu. Rób to od razu — zajmuje kilka minut.</p>
                HTML,
            ],
            [
                'slug' => 'adoptowany-zwierzak-pierwszy-tydzien',
                'category' => 'adopcja',
                'title' => 'Adoptowany zwierzak w nowym domu — pierwszy tydzień',
                'excerpt' => 'Zasada 3-3-3, bezpieczna przestrzeń i spokój. Jak przejść przez pierwsze dni bez stresu dla obu stron.',
                'body' => <<<'HTML'
                <p>Pierwsze dni po adopcji decydują o zaufaniu. Mniej znaczy więcej — mniej bodźców, mniej gości, mniej presji.</p>
                <h2>Zasada 3-3-3</h2>
                <ul>
                    <li><strong>3 dni</strong> — zwierzak jest przytłoczony, dużo śpi lub się chowa.</li>
                    <li><strong>3 tygodnie</strong> — poznaje rytm domu, pokazuje charakter.</li>
                    <li><strong>3 miesiące</strong> — czuje się u siebie.</li>
                </ul>
                <h2>Co przygotować</h2>
                <ul>
                    <li>Ciche miejsce z legowiskiem, z dala od przejść.</li>
                    <li>Stały plan dnia — karmienie i spacery o podobnych porach.</li>
                    <li>Adresatka i aktualny chip od pierwszego dnia.</li>
                </ul>
                HTML,
            ],
        ];

        foreach ($posts as $i => $data) {
            Post::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $author->id,
                    'blog_category_id' => $categories[$data['category']]->id,
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => trim($data['body']),
                    'status' => 'published',
                    'published_at' => now()->subDays(count($posts) - $i)->setTime(9, 0),
                ],
            );
        }
    }
}
