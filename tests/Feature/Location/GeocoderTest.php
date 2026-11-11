<?php

namespace Tests\Feature\Location;

use App\Models\City;
use App\Models\Voivodeship;
use App\Services\Geocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// ---------------------------
// Geokodowanie wpisanej lokalizacji (tekst -> lat/lng), gdy brak pinezki na mapie.
// Nominatim jest podstawiony przez Http::fake - testy nie wychodzą do sieci.
// ---------------------------
class GeocoderTest extends TestCase
{
    use RefreshDatabase;

    private Voivodeship $slaskie;

    private City $katowice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slaskie = Voivodeship::forceCreate(['name_pl' => 'śląskie', 'name_en' => 'Silesia', 'teryt_code' => '24']);
        $this->katowice = City::forceCreate(['name_pl' => 'Katowice', 'name_en' => 'Katowice', 'voivodeship_id' => $this->slaskie->id]);
    }

    // ** Wynik Nominatim w formacie jsonv2 z kodem województwa
    private function nominatimHit(float $lat, float $lng, string $iso = 'PL-24'): array
    {
        return ['lat' => (string) $lat, 'lon' => (string) $lng, 'address' => ['ISO3166-2-lvl4' => $iso]];
    }

    public function test_search_endpoint_returns_precise_coordinates_for_address(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([$this->nominatimHit(50.2585, 19.0272)])]);

        $this->getJson('/location/search?'.http_build_query([
            'q' => 'Rynek',
            'voivodeship_id' => $this->slaskie->id,
            'city_id' => $this->katowice->id,
        ]))
            ->assertOk()
            ->assertJson(['found' => true, 'lat' => 50.2585, 'lng' => 19.0272, 'precise' => true]);

        // ** Miejscowość doklejona do opisu
        Http::assertSent(fn ($request) => $request['q'] === 'Rynek, Katowice');
    }

    public function test_falls_back_to_city_center_when_address_not_found(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::sequence()
            ->push([])
            ->push([$this->nominatimHit(50.26, 19.02)])]);

        $hit = app(Geocoder::class)->search('Nieistniejąca 999', 'Katowice', $this->slaskie);

        $this->assertSame(['lat' => 50.26, 'lng' => 19.02, 'precise' => false], $hit);
    }

    public function test_skips_results_from_other_voivodeship(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
            $this->nominatimHit(52.0, 21.0, 'PL-14'),
            $this->nominatimHit(50.26, 19.02, 'PL-24'),
        ])]);

        $hit = app(Geocoder::class)->search('Park', 'Katowice', $this->slaskie);

        $this->assertSame(50.26, $hit['lat']);
    }

    public function test_fills_missing_coordinates_in_request(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([$this->nominatimHit(50.2585, 19.0272)])]);

        $request = Request::create('/', 'POST', [
            'location_text' => 'Rynek',
            'voivodeship_id' => $this->slaskie->id,
            'city_id' => $this->katowice->id,
        ]);

        app(Geocoder::class)->fillMissingCoordinates($request, 'location_text');

        $this->assertEquals(50.2585, $request->input('latitude'));
        $this->assertEquals(19.0272, $request->input('longitude'));
    }

    public function test_does_not_override_coordinates_set_by_user(): void
    {
        Http::fake();

        $request = Request::create('/', 'POST', ['location_text' => 'Rynek', 'latitude' => '50.1', 'longitude' => '19.1']);

        app(Geocoder::class)->fillMissingCoordinates($request, 'location_text', withCity: false);

        $this->assertSame('50.1', $request->input('latitude'));
        Http::assertNothingSent();
    }

    public function test_store_shows_friendly_message_when_location_cannot_be_found(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([])]);

        $this->post('/animals', [
            'location_text' => 'Nigdzie',
            'voivodeship_id' => $this->slaskie->id,
            'city_id' => $this->katowice->id,
        ])->assertSessionHasErrors([
            'latitude' => 'Nie udało się ustalić miejsca na mapie - kliknij na mapie, żeby zaznaczyć, gdzie to było.',
        ]);
    }
}
