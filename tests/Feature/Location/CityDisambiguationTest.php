<?php

namespace Tests\Feature\Location;

use App\Models\City;
use App\Models\Voivodeship;
use App\Services\Geocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// ---------------------------
// Miejscowości o tej samej nazwie w jednym województwie (np. kilka "Dąbrowa")
// rozróżniane gminą, powiatem i rodzajem - w podpowiedziach, odwrotnym i zwykłym geokodowaniu.
// ---------------------------
class CityDisambiguationTest extends TestCase
{
    use RefreshDatabase;

    private Voivodeship $malopolskie;

    private City $dabrowaGnojnik;

    private City $dabrowaBobowa;

    private City $krakow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->malopolskie = Voivodeship::forceCreate(['name_pl' => 'małopolskie', 'name_en' => 'Lesser Poland', 'teryt_code' => '12']);

        $this->dabrowaGnojnik = City::create(['name_pl' => 'Dąbrowa', 'voivodeship_id' => $this->malopolskie->id, 'kind' => 'wieś', 'gmina' => 'Gnojnik', 'powiat' => 'brzeski']);
        $this->dabrowaBobowa = City::create(['name_pl' => 'Dąbrowa', 'voivodeship_id' => $this->malopolskie->id, 'kind' => 'wieś', 'gmina' => 'Bobowa', 'powiat' => 'gorlicki']);
        $this->krakow = City::create(['name_pl' => 'Kraków', 'voivodeship_id' => $this->malopolskie->id, 'kind' => 'miasto', 'gmina' => 'Kraków', 'powiat' => 'Kraków']);
    }

    public function test_marks_only_repeated_names_as_ambiguous(): void
    {
        $this->assertTrue($this->dabrowaGnojnik->fresh()->is_ambiguous);
        $this->assertTrue($this->dabrowaBobowa->fresh()->is_ambiguous);
        $this->assertFalse($this->krakow->fresh()->is_ambiguous);

        $this->assertSame('Dąbrowa (wieś, gm. Gnojnik, pow. brzeski)', $this->dabrowaGnojnik->fresh()->label);
        $this->assertSame('Kraków', $this->krakow->fresh()->label);
    }

    public function test_rename_and_delete_refresh_ambiguity(): void
    {
        $this->dabrowaBobowa->update(['name_pl' => 'Dąbrowa Mała']);

        $this->assertFalse($this->dabrowaGnojnik->fresh()->is_ambiguous);

        City::create(['name_pl' => 'Kraków', 'voivodeship_id' => $this->malopolskie->id, 'gmina' => 'Inna']);
        $this->assertTrue($this->krakow->fresh()->is_ambiguous);
    }

    public function test_search_returns_details_for_repeated_names(): void
    {
        $this->getJson('/cities/search?'.http_build_query(['voivodeship_id' => $this->malopolskie->id, 'q' => 'Dąb']))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $this->dabrowaBobowa->id, 'details' => 'wieś, gm. Bobowa, pow. gorlicki'])
            ->assertJsonFragment(['id' => $this->dabrowaGnojnik->id, 'label' => 'Dąbrowa (wieś, gm. Gnojnik, pow. brzeski)']);

        $this->getJson('/cities/search?'.http_build_query(['voivodeship_id' => $this->malopolskie->id, 'q' => 'Kra']))
            ->assertJsonFragment(['id' => $this->krakow->id, 'details' => null, 'label' => 'Kraków']);
    }

    // ** Odpowiedź reverse w formacie jsonv2
    private function fakeReverse(array $address): void
    {
        Http::fake(['nominatim.openstreetmap.org/reverse*' => Http::response([
            'address' => ['ISO3166-2-lvl4' => 'PL-12', 'village' => 'Dąbrowa', ...$address],
        ])]);
    }

    public function test_reverse_picks_city_by_municipality(): void
    {
        $this->fakeReverse(['municipality' => 'gmina Bobowa', 'county' => 'powiat gorlicki']);

        $this->getJson('/location/reverse?lat=49.7&lng=20.9')
            ->assertOk()
            ->assertJson(['city_id' => $this->dabrowaBobowa->id, 'city_name' => 'Dąbrowa (wieś, gm. Bobowa, pow. gorlicki)']);
    }

    public function test_reverse_falls_back_to_county(): void
    {
        $this->fakeReverse(['county' => 'powiat brzeski']);

        $this->getJson('/location/reverse?lat=49.9&lng=20.6')
            ->assertJson(['city_id' => $this->dabrowaGnojnik->id]);
    }

    public function test_reverse_leaves_city_empty_when_still_ambiguous(): void
    {
        $this->fakeReverse([]);

        $this->getJson('/location/reverse?lat=49.9&lng=20.6')
            ->assertJson(['city_id' => null, 'city_name' => 'Dąbrowa']);
    }

    public function test_geocoding_adds_gmina_for_repeated_name(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
            ['lat' => '49.9', 'lon' => '20.6', 'address' => ['ISO3166-2-lvl4' => 'PL-12']],
        ])]);

        app(Geocoder::class)->search(null, $this->dabrowaGnojnik->fresh()->geocodeName(), $this->malopolskie);

        Http::assertSent(fn ($request) => $request['q'] === 'Dąbrowa, gmina Gnojnik');
    }
}
