<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Voivodeship;
use App\Services\Geocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// ---------------------------
// Odwrotne geokodowanie pinezki z mapy (lat/lng -> województwo/miejscowość).
// Korzysta z darmowego, publicznego Nominatim (OpenStreetMap) i dopasowuje wynik
// do naszej bazy: kod ISO3166-2 (np. "PL-24") pokrywa się 1:1 z kodem TERC województwa,
// więc województwo jest zawsze pewne - niepewna bywa tylko dokładna nazwa miejscowości.
// ---------------------------

class LocationController extends Controller
{
    public function reverse(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        // ** Nominatim bywa wolny/niedostępny - to tylko podpowiedź, więc każdy błąd
        // (w tym brak połączenia) kończy się grzecznym "resolved: false", nie 500-tką
        try {
            $response = Http::withHeaders([
                    'User-Agent' => 'Noskiem.pl (kontakt@noskiem.pl)',
                ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $data['lat'],
                    'lon' => $data['lng'],
                    'zoom' => 18,
                    'addressdetails' => 1,
                    'accept-language' => 'pl',
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return response()->json(['resolved' => false]);
        }

        if (! $response->ok()) {
            return response()->json(['resolved' => false]);
        }

        $address = $response->json('address', []);

        // "PL-24" -> "24", dokładnie kod TERC naszego wojewodztwa
        $tercCode = isset($address['ISO3166-2-lvl4'])
            ? substr($address['ISO3166-2-lvl4'], 3)
            : null;

        $voivodeship = $tercCode ? Voivodeship::where('teryt_code', $tercCode)->first() : null;

        $localityName = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['hamlet'] ?? null;

        // "quarter" jest bardziej szczegółowy niż "suburb"/"city_district" (dzielnica dzielnicy)
        $district = $address['quarter'] ?? $address['suburb'] ?? $address['city_district'] ?? null;

        // Ulica z numerem domu, jeśli Nominatim je zna dla tego punktu
        $street = $address['road'] ?? null;
        if ($street && ! empty($address['house_number'])) {
            $street .= ' '.$address['house_number'];
        }

        // Dopasowanie po dokładnej nazwie - nazewnictwo OSM i SIMC zwykle się pokrywa,
        // ale przy rozbieżności zostawiamy city_id puste i podpowiadamy tekst do ręcznego wyboru
        $city = ($voivodeship && $localityName)
            ? $this->matchCity($voivodeship, $localityName, $address)
            : null;

        $locationTextSuggestion = collect([$street, $district, $localityName])->filter()->implode(', ') ?: null;

        return response()->json([
            'resolved' => (bool) $voivodeship,
            'voivodeship_id' => $voivodeship?->id,
            'city_id' => $city?->id,
            'city_name' => $city?->label ?? $localityName,
            'location_text_suggestion' => $locationTextSuggestion,
        ]);
    }

    // ---------------------------
    // Szuka miejscowości o nazwie z OSM w województwie. Przy kilku o tej samej nazwie
    // (np. "Dąbrowa") zawężamy po gminie ("municipality": "gmina Gnojnik"), a potem po powiecie
    // ("county": "powiat brzeski"). Jeśli nadal nie da się wskazać jednej - zwracamy null,
    // żeby użytkownik wybrał ręcznie, zamiast podstawiać przypadkową.
    // ---------------------------
    private function matchCity(Voivodeship $voivodeship, string $localityName, array $address): ?City
    {
        $candidates = City::where('voivodeship_id', $voivodeship->id)->where('name_pl', $localityName)->get();

        if ($candidates->count() <= 1) {
            return $candidates->first();
        }

        $filters = [
            'gmina'  => $this->stripPrefix($address['municipality'] ?? null, 'gmina'),
            'powiat' => $this->stripPrefix($address['county'] ?? null, 'powiat'),
        ];

        foreach ($filters as $column => $value) {
            if ($value === null) {
                continue;
            }

            $narrowed = $candidates->filter(fn (City $c) => mb_strtolower((string) $c->$column) === mb_strtolower($value));

            if ($narrowed->count() === 1) {
                return $narrowed->first();
            }

            // ** Zawężamy dalej tylko gdy filtr coś zostawił - inaczej OSM i TERC różnią się nazewnictwem
            if ($narrowed->isNotEmpty()) {
                $candidates = $narrowed;
            }
        }

        return null;
    }

    // ** "gmina Gnojnik" -> "Gnojnik", "powiat brzeski" -> "brzeski"
    private function stripPrefix(?string $value, string $prefix): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim(preg_replace('/^'.$prefix.'\s+/iu', '', $value));
    }

    // ---------------------------
    // Geokodowanie "w przód" (opis lokalizacji + miejscowość -> lat/lng), gdy użytkownik
    // wpisał miejsce ręcznie, ale nie kliknął na mapie. Front ustawia wtedy pinezkę sam.
    // ---------------------------
    public function search(Request $request, Geocoder $geocoder)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:255',
            'voivodeship_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
        ]);

        $voivodeship = Voivodeship::find($data['voivodeship_id'] ?? null);

        $city = $voivodeship
            ? City::where('voivodeship_id', $voivodeship->id)->find($data['city_id'] ?? null)
            : null;

        $hit = $geocoder->search($data['q'] ?? null, $city?->geocodeName(), $voivodeship);

        if (! $hit) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'lat' => $hit['lat'],
            'lng' => $hit['lng'],
            'precise' => $hit['precise'],
        ]);
    }
}
