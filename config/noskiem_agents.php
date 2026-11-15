<?php

// ---------------------------
// Agenci noskiem - wolontariusze z kontem, którzy dostają prośbę o pomoc, gdy w ich
// obszarze działania pojawi się nowe ogłoszenie o zaginionym zwierzaku.
// Patrz App\Services\AgentNotifier i php artisan agents:notify.
// ---------------------------

return [

    // ** Wyłącznik wysyłki (rejestracja i profil działają dalej)
    'notify_enabled' => env('AGENTS_NOTIFY_ENABLED', true),

    // ** Obszary działania
    'max_areas' => 20,
    'min_radius_km' => 1,
    'max_radius_km' => 50,
    'default_radius_km' => 5,

    // ** Starszych ogłoszeń nie rozsyłamy (np. po dłuższej przerwie crona) -
    // prośba o pomoc ma sens, póki ślad jest świeży
    'listing_max_age_days' => 3,

    // ** Najwyżej tyle ogłoszeń w jednym mailu zbiorczym; reszta jako "i jeszcze N"
    'max_items_per_mail' => 10,
];
