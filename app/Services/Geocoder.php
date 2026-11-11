<?php

namespace App\Services;

use App\Models\City;
use App\Models\Voivodeship;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// ---------------------------
// Geokodowanie "w przód" (tekst -> lat/lng) przez Nominatim (OpenStreetMap).
// Używane, gdy użytkownik wpisał lokalizację ręcznie, ale nie kliknął pinezki na mapie:
// - z frontu przez GET /location/search (pinezka ustawia się sama, użytkownik ją widzi),
// - z kontrolerów jako zabezpieczenie przed walidacją (JS nie zdążył / był wyłączony).
// Błędy są ciche - brak wyniku oznacza po prostu komunikat "zaznacz miejsce na mapie".
// ---------------------------
class Geocoder
{
    // ** Wyniki cache'ujemy - Nominatim ma limit ~1 zapytania/s, a to samo miejsce
    // jest odpytywane dwa razy (front + ewentualnie backend przy wysyłce formularza)
    private const CACHE_TTL_SECONDS = 86400;

    // ---------------------------
    // Szuka współrzędnych dla opisu lokalizacji. Najpierw dokładnie (opis + miejscowość),
    // a jeśli się nie uda - samą miejscowość (wtedy precise=false, pinezka w jej środku).
    // Województwo służy do odsiania miejscowości o tej samej nazwie w innych regionach.
    // Zwraca ['lat' => float, 'lng' => float, 'precise' => bool] albo null.
    // ---------------------------
    public function search(?string $text, ?string $cityName = null, ?Voivodeship $voivodeship = null): ?array
    {
        $text = trim((string) $text);
        $cityName = trim((string) $cityName);

        $queries = [];

        if ($text !== '') {
            $queries[] = [$this->joinQuery($text, $cityName), true];
        }

        if ($cityName !== '') {
            $queries[] = [$cityName, false];
        }

        foreach ($queries as [$query, $precise]) {
            $hit = $this->query($query, $voivodeship);

            if ($hit) {
                return [...$hit, 'precise' => $precise];
            }
        }

        return null;
    }

    // ---------------------------
    // Uzupełnia brakujące latitude/longitude w żądaniu na podstawie wpisanego tekstu.
    // Wywoływane PRZED walidacją - jeśli geokodowanie się nie uda, pola zostają puste
    // i walidacja zwraca przyjazny komunikat (patrz coordinatesMessages()).
    // ---------------------------
    public function fillMissingCoordinates(Request $request, string $textField, bool $withCity = true): void
    {
        if ($request->filled('latitude') && $request->filled('longitude')) {
            return;
        }

        $voivodeship = $withCity ? Voivodeship::find($request->input('voivodeship_id')) : null;

        // ** Miejscowość tylko z wybranego województwa - inaczej to manipulacja formularzem
        $city = ($withCity && $voivodeship)
            ? City::where('voivodeship_id', $voivodeship->id)->find($request->input('city_id'))
            : null;

        $hit = $this->search($request->input($textField), $city?->name_pl, $voivodeship);

        if ($hit) {
            $request->merge([
                'latitude' => round($hit['lat'], 6),
                'longitude' => round($hit['lng'], 6),
            ]);
        }
    }

    // ** Komunikaty walidacji współrzędnych zrozumiałe dla zwykłego użytkownika
    public static function coordinatesMessages(): array
    {
        $message = 'Nie udało się ustalić miejsca na mapie - kliknij na mapie, żeby zaznaczyć, gdzie to było.';

        return [
            'latitude.required' => $message,
            'longitude.required' => $message,
            'latitude.numeric' => $message,
            'longitude.numeric' => $message,
            'latitude.between' => $message,
            'longitude.between' => $message,
        ];
    }

    // ** Dokleja miejscowość do opisu, chyba że użytkownik już ją wpisał ("Bronowice, Kraków")
    private function joinQuery(string $text, string $cityName): string
    {
        if ($cityName === '' || Str::contains(Str::lower($text), Str::lower($cityName))) {
            return $text;
        }

        return $text.', '.$cityName;
    }

    // ---------------------------
    // Pojedyncze zapytanie do Nominatim, zawężone do Polski. Bierzemy kilka wyników
    // i wybieramy pierwszy z właściwego województwa (kod ISO "PL-24" = TERC "24").
    // ---------------------------
    private function query(string $query, ?Voivodeship $voivodeship): ?array
    {
        $cacheKey = 'geocoder:'.md5(Str::lower($query).'|'.($voivodeship?->teryt_code ?? ''));

        // ** Pusty wynik zapisujemy jako [] - Cache::remember nie zapamiętuje null
        $hit = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($query, $voivodeship) {
            try {
                $response = Http::withHeaders([
                        'User-Agent' => 'Noskiem.pl (kontakt@noskiem.pl)',
                    ])
                    ->timeout(5)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'jsonv2',
                        'q' => $query,
                        'countrycodes' => 'pl',
                        'limit' => 5,
                        'addressdetails' => 1,
                        'accept-language' => 'pl',
                    ]);
            } catch (ConnectionException) {
                return null; // Brak połączenia nie trafia do cache - spróbujemy następnym razem
            }

            if (! $response->ok()) {
                return null;
            }

            foreach ((array) $response->json() as $result) {
                $isoCode = $result['address']['ISO3166-2-lvl4'] ?? null;

                if ($voivodeship && $isoCode !== 'PL-'.$voivodeship->teryt_code) {
                    continue;
                }

                return ['lat' => (float) $result['lat'], 'lng' => (float) $result['lon']];
            }

            return [];
        });

        return $hit ?: null;
    }
}
