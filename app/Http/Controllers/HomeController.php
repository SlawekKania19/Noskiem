<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\HomepageListings;
use Illuminate\Http\Request;

// ---------------------------
// Kontroler strony głównej - dla każdego statusu (lost / found) dwie grupy ogłoszeń:
// "Ostatnio dodane" i losowane z wagami "Wciąż szukamy" (patrz App\Services\HomepageListings)
// ---------------------------

class HomeController extends Controller
{
    public function index(Request $request, HomepageListings $listings)
    {
        // ** Limit z panelu obowiązuje osobno dla każdej z dwóch grup
        $limit = max(1, (int) Setting::get('homepage_items_limit', '6'));

        // ** Okolica odwiedzającego - tylko jeśli sam ją włączył na stronie (ciasteczko)
        $location = HomepageListings::locationFromCookie($request->cookie(HomepageListings::LOCATION_COOKIE));

        $sections = [];

        foreach (['lost', 'found'] as $status) {
            $latest = $listings->latest($status, $limit);

            $sections[$status] = [
                'latest'  => $latest,
                'rotated' => $listings->rotated($status, $limit, $latest->modelKeys(), $location),
            ];
        }

        return view('home', [
            'sections' => $sections,
            'nearby'   => $location !== null,
        ]);
    }
}
