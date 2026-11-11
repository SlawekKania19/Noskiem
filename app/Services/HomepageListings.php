<?php

namespace App\Services;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Ogłoszenia na stronę główną - dwie grupy dla każdego statusu (lost / found):
// - latest()  - "Ostatnio dodane": najnowsze zatwierdzone
// - rotated() - "Wciąż szukamy": losowanie z wagami spośród pozostałych. Im starsze
//   ogłoszenie, tym niższa waga (ale nigdy zero), zgłoszenie "widziałem" odświeża,
//   brak zdjęcia obniża, a okolica odwiedzającego (jeśli ją włączył) podbija.
// Losowanie jest deterministyczne w obrębie okna czasu (config homepage.rotation.minutes) -
// "los" ogłoszenia liczymy z jego id i numeru okna, więc bez cache i bez skakania kart
// przy odświeżeniu. Parametry: config/homepage.php
// ---------------------------

class HomepageListings
{
    // Ciasteczko z przybliżoną okolicą odwiedzającego - ustawia je JS na stronie głównej
    // po kliknięciu "z mojej okolicy" (nieszyfrowane, patrz bootstrap/app.php)
    public const LOCATION_COOKIE = 'noskiem_loc';

    protected const RELATIONS = ['species', 'breed', 'voivodeship', 'city', 'photos'];

    public function latest(string $status, int $limit): Collection
    {
        return Animal::where('mod_status', 'approved')
            ->where('status', $status)
            ->with(self::RELATIONS)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int>  $excludeIds  ogłoszenia już pokazane w "Ostatnio dodane"
     * @param  array{lat: float, lng: float}|null  $location  okolica odwiedzającego
     */
    public function rotated(string $status, int $limit, array $excludeIds = [], ?array $location = null, ?Carbon $now = null): Collection
    {
        $now ??= now();
        $cfg = config('homepage.rotation');
        $slot = intdiv($now->getTimestamp(), max(1, (int) $cfg['minutes']) * 60);

        // ** Lekkie zapytanie - same pola potrzebne do wag, bez modeli Eloquenta
        $rows = DB::table('animals')
            ->where('mod_status', 'approved')
            ->where('status', $status)
            ->whereNotIn('id', $excludeIds)
            ->select(['id', 'created_at', 'latitude', 'longitude'])
            ->selectRaw('exists(select 1 from photos where photos.animal_id = animals.id and photos.sighting_id is null) as has_photo')
            ->selectRaw("(select max(s.created_at) from sightings s where s.animal_id = animals.id and s.mod_status = 'approved') as last_sighting_at")
            ->get();

        // ** Losowanie z wagami bez powtórzeń (Efraimidis-Spirakis): każdemu ogłoszeniu
        // liczymy klucz log(u)/waga, gdzie u to jego "los" z (0,1), i bierzemy najwyższe
        $ids = $rows
            ->map(fn ($row) => [
                'id'  => (int) $row->id,
                'key' => log($this->draw((int) $row->id, $slot)) / $this->weight($row, $now, $cfg, $location),
            ])
            ->sortByDesc('key')
            ->take($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return new Collection;
        }

        $order = $ids->flip();

        return Animal::with(self::RELATIONS)
            ->findMany($ids->all())
            ->sortBy(fn (Animal $animal) => $order[$animal->id])
            ->values();
    }

    // ** "Los" ogłoszenia w danym oknie czasu - liczba z (0,1), zawsze ta sama dla pary id + okno
    protected function draw(int $id, int $slot): float
    {
        // 13 cyfr szesnastkowych = 52 bity, mieści się dokładnie we float
        return (hexdec(substr(sha1($id.'|'.$slot), 0, 13)) + 1) / (16 ** 13 + 2);
    }

    protected function weight(object $row, Carbon $now, array $cfg, ?array $location): float
    {
        // ** Wiek liczony od dodania albo od ostatniego "widziałem" - co nowsze
        $fresh = Carbon::parse($row->created_at);

        if ($row->last_sighting_at !== null) {
            $fresh = $fresh->max(Carbon::parse($row->last_sighting_at));
        }

        $ageDays = max(0, $fresh->diffInSeconds($now, false)) / 86400;
        $weight = max((float) $cfg['min_weight'], 0.5 ** ($ageDays / max(0.1, (float) $cfg['half_life_days'])));

        if (! $row->has_photo) {
            $weight *= (float) $cfg['no_photo_factor'];
        }

        if ($location !== null && $row->latitude !== null && $row->longitude !== null) {
            $km = $this->distanceKm($location['lat'], $location['lng'], (float) $row->latitude, (float) $row->longitude);
            $weight *= 1 + ((float) $cfg['near_boost'] - 1) * 0.5 ** ($km / max(1, (float) $cfg['near_half_km']));
        }

        // Waga musi być dodatnia - dzielimy przez nią
        return max($weight, 1e-6);
    }

    // Odległość po kuli ziemskiej (haversine), w km
    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($a)));
    }

    /**
     * Okolica z ciasteczka w formacie "50.3_19.0" (zaokrąglona do 0,1 stopnia, ok. 10 km -
     * dokładne położenie nie opuszcza przeglądarki). Błędna wartość = brak okolicy.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function locationFromCookie(mixed $value): ?array
    {
        if (! is_string($value) || ! preg_match('/^(-?\d{1,2}(?:\.\d)?)_(-?\d{1,3}(?:\.\d)?)$/', $value, $m)) {
            return null;
        }

        [$lat, $lng] = [(float) $m[1], (float) $m[2]];

        return abs($lat) <= 90 && abs($lng) <= 180 ? ['lat' => $lat, 'lng' => $lng] : null;
    }
}
