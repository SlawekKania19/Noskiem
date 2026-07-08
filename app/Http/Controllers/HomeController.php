<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Setting;

// ---------------------------
// Kontroler strony głównej — sekcje "Poszukiwane" (lost) i "Widziane" (found)
// ---------------------------

class HomeController extends Controller
{
    // Wyświetla stronę główną z kilkoma najnowszymi zatwierdzonymi ogłoszeniami dla każdego statusu
    public function index()
    {
        $limit = (int) Setting::get('homepage_items_limit', '6');

        $lostAnimals = Animal::where('mod_status', 'approved')
            ->where('status', 'lost')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->latest()
            ->limit($limit)
            ->get();

        $foundAnimals = Animal::where('mod_status', 'approved')
            ->where('status', 'found')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->latest()
            ->limit($limit)
            ->get();

        return view('home', compact('lostAnimals', 'foundAnimals'));
    }
}
