<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Setting;
use App\Models\Sighting;
use App\Services\HomepageListings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Matching\MatchingFixtures;
use Tests\TestCase;

// ---------------------------
// Strona główna: "Ostatnio dodane" + losowane z wagami "Wciąż szukamy"
// (App\Services\HomepageListings). Losowanie jest deterministyczne (los z id i numeru
// okna czasu), więc testy częstości są powtarzalne.
// ---------------------------

class HomepageListingsTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    private HomepageListings $listings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        $this->listings = app(HomepageListings::class);
    }

    // ** Zatwierdzone ogłoszenie "zaginął" dodane $daysAgo dni temu, domyślnie ze zdjęciem
    private function listing(float $daysAgo, array $attrs = [], bool $photo = true): Animal
    {
        $animal = $this->animal('approved', $attrs);
        Animal::whereKey($animal->id)->toBase()->update(['created_at' => now()->subMinutes((int) ($daysAgo * 1440))]);

        if ($photo) {
            $this->photo(['animal_id' => $animal->id]);
        }

        return $animal;
    }

    // ** Ile razy każde ogłoszenie trafiło do losowania z limitem 1 w kolejnych oknach (co godzinę)
    private function frequency(int $slots, ?array $location = null): array
    {
        $counts = [];
        $start = Carbon::parse('2026-11-11 00:30:00');

        for ($i = 0; $i < $slots; $i++) {
            // Czas losowania się zmienia, ale wiek ogłoszeń liczymy od tej samej chwili co test
            $id = $this->pick(1, $location, $start->copy()->addHours($i))[0] ?? null;
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        return $counts;
    }

    private function pick(int $limit, ?array $location = null, ?Carbon $slotTime = null, array $exclude = []): array
    {
        // ** Okno losowania z $slotTime, ale wagi wg bieżącego wieku ogłoszeń - podmieniamy tylko
        // długość okna tak, by numer okna zależał od $slotTime
        if ($slotTime === null) {
            return $this->listings->rotated('lost', $limit, $exclude, $location)->modelKeys();
        }

        config(['homepage.rotation.minutes' => 60]);
        $salt = intdiv($slotTime->getTimestamp(), 3600);

        return (new class($salt) extends HomepageListings {
            public function __construct(private int $salt) {}

            protected function draw(int $id, int $slot): float
            {
                return parent::draw($id, $this->salt);
            }
        })->rotated('lost', $limit, $exclude, $location)->modelKeys();
    }

    public function test_losowanie_jest_stale_w_obrebie_okna_i_zmienia_sie_miedzy_oknami(): void
    {
        foreach (range(1, 12) as $i) {
            $this->listing($i);
        }

        $at = Carbon::parse('2026-11-11 10:05:00');
        $first = $this->listings->rotated('lost', 4, [], null, $at)->modelKeys();

        $this->assertCount(4, $first);
        $this->assertSame($first, $this->listings->rotated('lost', 4, [], null, $at->copy()->addMinutes(50))->modelKeys());

        // ** W kolejnych godzinach zestaw się zmienia
        $later = collect(range(1, 5))->map(fn ($h) => $this->listings->rotated('lost', 4, [], null, $at->copy()->addHours($h))->modelKeys());
        $this->assertTrue($later->contains(fn ($ids) => $ids !== $first));
    }

    public function test_pomija_ostatnio_dodane_niezatwierdzone_i_inny_status(): void
    {
        $shownAsLatest = $this->listing(1);
        $ok = $this->listing(5);
        $this->listing(5, ['mod_status' => 'resolved']);
        $this->listing(5, ['mod_status' => 'pending']);
        $this->listing(5, ['status' => 'found']);

        $this->assertSame([$ok->id], $this->pick(10, exclude: [$shownAsLatest->id]));
    }

    public function test_starsze_ogloszenie_pojawia_sie_rzadziej_ale_sie_pojawia(): void
    {
        $fresh = $this->listing(1);
        $old = $this->listing(120);

        $counts = $this->frequency(400);

        // ** Waga starego = minimum 0,1 wobec ~0,95 świeżego -> ok. 10% losowań
        $this->assertGreaterThan(0, $counts[$old->id] ?? 0);
        $this->assertGreaterThan(5 * $counts[$old->id], $counts[$fresh->id]);
    }

    public function test_zgloszenie_widzialem_odswieza_a_brak_zdjecia_obniza_wage(): void
    {
        $old = $this->listing(120);
        $seen = $this->listing(120);
        Sighting::forceCreate([
            'animal_id' => $seen->id, 'mod_status' => 'approved', 'description' => 'Widziany', 'date_seen' => now(),
            'location' => 'Park', 'contact_name' => 'Ola', 'contact_email' => 'ola@example.com',
        ]);

        $counts = $this->frequency(300);
        $this->assertGreaterThan(3 * ($counts[$old->id] ?? 0), $counts[$seen->id]);

        // ** Niezatwierdzone "widziałem" nie odświeża
        Sighting::query()->update(['mod_status' => 'pending']);
        $counts = $this->frequency(300);
        $this->assertLessThan(2 * ($counts[$old->id] ?? 1), $counts[$seen->id]);

        Animal::query()->delete();
        $withPhoto = $this->listing(3);
        $noPhoto = $this->listing(3, photo: false);
        $counts = $this->frequency(300);
        $this->assertGreaterThan($counts[$noPhoto->id], $counts[$withPhoto->id]);
    }

    public function test_okolica_odwiedzajacego_podbija_pobliskie_ogloszenia(): void
    {
        // Katowice (z danych bazowych) i Gdańsk - ten sam wiek
        $near = $this->listing(10);
        $far = $this->listing(10, ['latitude' => 54.35, 'longitude' => 18.65]);

        $without = $this->frequency(300);
        $with = $this->frequency(300, ['lat' => 50.3, 'lng' => 19.0]);

        // ** Bez lokalizacji mniej więcej po równo, z lokalizacją wyraźnie częściej bliskie
        $this->assertLessThan(2 * $without[$far->id], $without[$near->id]);
        $this->assertGreaterThan(3 * $with[$far->id], $with[$near->id]);
        $this->assertGreaterThan(0, $with[$far->id]);
    }

    public function test_ciasteczko_z_okolica(): void
    {
        $this->assertSame(['lat' => 50.3, 'lng' => 19.0], HomepageListings::locationFromCookie('50.3_19.0'));
        $this->assertSame(['lat' => -33.9, 'lng' => 151.2], HomepageListings::locationFromCookie('-33.9_151.2'));

        foreach ([null, '', 'abc', '50.26123_19.02', '95.0_19.0', '50.3_190.0', '50.3,19.0', ['x']] as $bad) {
            $this->assertNull(HomepageListings::locationFromCookie($bad));
        }
    }

    public function test_strona_glowna_pokazuje_obie_grupy_bez_powtorzen(): void
    {
        Setting::set('homepage_items_limit', '2');

        $newest = [$this->listing(0.1, ['animal_name' => 'Nowyjeden']), $this->listing(0.2, ['animal_name' => 'Nowydwa'])];
        $older = [$this->listing(20, ['animal_name' => 'Staryjeden']), $this->listing(30, ['animal_name' => 'Starydwa']),
            $this->listing(40, ['animal_name' => 'Starytrzy'])];

        $response = $this->get('/')->assertOk()
            ->assertSee('Ostatnio dodane')
            ->assertSee('Wciąż szukamy')
            ->assertSee('Pokazuj częściej z mojej okolicy');

        $sections = $response->viewData('sections');
        $this->assertSame([$newest[0]->id, $newest[1]->id], $sections['lost']['latest']->modelKeys());
        $this->assertCount(2, $sections['lost']['rotated']);
        $this->assertEmpty(array_intersect($sections['lost']['rotated']->modelKeys(), [$newest[0]->id, $newest[1]->id]));
        $this->assertFalse($response->viewData('nearby'));

        // ** Z ciasteczkiem okolicy - stan "włączone" i przycisk wyłączenia
        $this->withUnencryptedCookie(HomepageListings::LOCATION_COOKIE, '50.3_19.0')->get('/')
            ->assertOk()
            ->assertViewHas('nearby', true)
            ->assertSee('Częściej pokazujemy ogłoszenia z Twojej okolicy');
    }

    public function test_bez_starszych_ogloszen_druga_grupa_sie_nie_pokazuje(): void
    {
        $this->listing(1);

        $this->get('/')->assertOk()
            ->assertSee('Ostatnio dodane')
            ->assertDontSee('Wciąż szukamy');
    }
}
