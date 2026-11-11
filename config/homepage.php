<?php

// ---------------------------
// Strona główna - sekcja "Wciąż szukamy": losowanie starszych ogłoszeń z wagami
// (App\Services\HomepageListings). Liczba ogłoszeń na sekcję jest w panelu Ustawień
// (homepage_items_limit) i obowiązuje osobno dla "Ostatnio dodane" i "Wciąż szukamy".
// ---------------------------

return [

    'rotation' => [
        // ** Co ile minut zmienia się wylosowany zestaw. W obrębie okna wynik jest stały -
        // odświeżenie strony nie przestawia kart
        'minutes' => (int) env('HOMEPAGE_ROTATION_MINUTES', 60),

        // ** Waga spada o połowę co tyle dni (liczone od dodania ogłoszenia albo od
        // ostatniego zatwierdzonego zgłoszenia "widziałem" - ono odświeża ogłoszenie)
        'half_life_days' => (float) env('HOMEPAGE_ROTATION_HALF_LIFE_DAYS', 14),

        // ** Dolna granica wagi - stare ogłoszenie pojawia się rzadko, ale się pojawia
        'min_weight' => (float) env('HOMEPAGE_ROTATION_MIN_WEIGHT', 0.1),

        // ** Mnożnik wagi dla ogłoszeń bez zdjęcia (karta bez zdjęcia mało daje)
        'no_photo_factor' => (float) env('HOMEPAGE_ROTATION_NO_PHOTO_FACTOR', 0.5),

        // ---------------------------
        // Okolica odwiedzającego (tylko gdy sam ją włączył - ciasteczko noskiem_loc).
        // Mnożnik wagi: near_boost w miejscu odwiedzającego, maleje z odległością -
        // nadwyżka ponad 1 spada o połowę co near_half_km. Dla 5 i 50 km:
        // 0 km x5, 50 km x3, 100 km x2, daleko x1
        // ---------------------------
        'near_boost' => (float) env('HOMEPAGE_ROTATION_NEAR_BOOST', 5),
        'near_half_km' => (float) env('HOMEPAGE_ROTATION_NEAR_HALF_KM', 50),
    ],
];
