<?php

// ---------------------------
// Dopasowania po zdjęciach (moduł Pythona noskiem_match/).
// Wszystko regulowane z .env - zmiana działa od następnego uruchomienia workera
// (o ile config nie jest zcache'owany; deploy robi optimize:clear).
// Część ustawień (kolejka, progi, filtry, maile) można nadpisać w panelu
// "Dopasowanie AI" (App\Support\MatchSettings) - wtedy .env to tylko wartość domyślna.
// Ustawienia modelu (nazwa, wersja, kadrowanie) są w noskiem_match/config.json -
// tam jest jedno źródło model_version, czytane też przez PHP.
// ---------------------------

return [

    // ** Główny włącznik. Domyślnie wyłączony - bez zainstalowanego środowiska
    // Pythona scheduler nie próbuje nic uruchamiać
    'enabled' => (bool) env('MATCH_ENABLED', false),

    // Ścieżka do interpretera Pythona z venv (torch, transformers...). Na hostingu
    // wspólny venv dla QA i produkcji poza repo: ~/venvs/noskiem_match/bin/python
    'python' => env('MATCH_PYTHON', base_path('noskiem_match/.venv/bin/python')),

    // Katalog modułu (cli.py, config.json)
    'module_path' => base_path('noskiem_match'),

    // ---------------------------
    // Regulacja kolejki liczenia wektorów
    // Kolejka = zdjęcia bez wektora dla bieżącej model_version; stan jest w bazie,
    // więc pauza / przesunięcie w czasie niczego nie gubi - zdjęcia po prostu czekają.
    // ---------------------------
    'queue' => [

        // ** Wstrzymanie przetwarzania (np. przy dużym ruchu) - kolejka rośnie, nic nie przepada
        'paused' => (bool) env('MATCH_PAUSED', false),

        // ** Okno godzin, w którym worker może pracować, np. "22:00-06:00" (może przechodzić
        // przez północ). Puste = całą dobę
        'hours' => env('MATCH_HOURS', ''),
        'timezone' => env('MATCH_TIMEZONE', 'Europe/Warsaw'),

        // ** Priorytet CPU procesu Pythona (nice 0–19; wyżej = niższy priorytet) -
        // przy obciążeniu strona WWW dostaje CPU pierwsza
        'nice' => (int) env('MATCH_NICE', 10),

        // ** Wątki torcha. Hosting (CloudLinux) pokazuje 32 rdzenie serwera, a konto ma limit -
        // bez jawnej wartości torch odpaliłby 32 wątki. Pomiar QA: 1 wątek ~2,7 s/zdjęcie,
        // 2 wątki ~1,7 s/zdjęcie
        'threads' => (int) env('MATCH_THREADS', 1),

        // ** Maks. zdjęć na jedno uruchomienie i limit czasu - worker kończy po bieżącym
        // zdjęciu, reszta idzie w następnym uruchomieniu (scheduler co minutę)
        'batch_limit' => (int) env('MATCH_BATCH_LIMIT', 30),
        'max_seconds' => (int) env('MATCH_MAX_SECONDS', 240),

        // ** Po pierwszym pobraniu wag z Hugging Face: true = bez odpytywania sieci przy
        // każdym starcie (pomiar QA: ładowanie modeli 2,4 s zamiast 12 s, odporne na awarię HF)
        'hf_offline' => (bool) env('MATCH_HF_OFFLINE', false),
    ],

    // ---------------------------
    // Wyszukiwanie podobnych ogłoszeń
    // ---------------------------
    'search' => [

        // ** Progi dotyczą CZYSTEGO podobieństwa zdjęć (cosinus, max po parach zdjęć).
        // Wartości WSTĘPNE (z 5 zapytań w dog_eval.py) - do kalibracji na naszych danych;
        // po zmianie model_version kalibrację trzeba powtórzyć. Celowo łagodnie: lepiej
        // pokazać kilka zbędnych niż przeoczyć prawdziwe dopasowanie.
        // Poniżej "possible" - nie pokazujemy; od "strong" - "bardzo podobne"
        'threshold_possible' => (float) env('MATCH_THRESHOLD_POSSIBLE', 0.60),
        'threshold_strong' => (float) env('MATCH_THRESHOLD_STRONG', 0.75),

        // Maks. wyników na liście
        'top_n' => (int) env('MATCH_TOP_N', 10),

        // ** Ilu kandydatów z wynikiem zapisujemy w logu, także PONIŻEJ progu
        // (do późniejszego sprawdzenia, czy próg nie ukrywał prawdziwych par)
        'log_floor_n' => (int) env('MATCH_LOG_FLOOR_N', 50),

        // ** Rasa i kolor - tylko lekki bonus do KOLEJNOŚCI (nigdy nie wyklucza kandydata,
        // nie wpływa na progi). 0 = wyłączone. Kolor: waga × podobieństwo zbiorów (Jaccard)
        'soft_weights' => [
            'breed' => (float) env('MATCH_WEIGHT_BREED', 0.02),
            'color' => (float) env('MATCH_WEIGHT_COLOR', 0.02),
        ],

        // ** Rasy traktowane jak "brak danych" - dwie "Rasa nieznana" to nie jest zgodność
        'neutral_breeds' => ['Rasa nieznana'],

        // ---------------------------
        // Twarde filtry puli kandydatów - każdy da się wyłączyć
        // ---------------------------
        'filters' => [
            // Zaginiony szukamy wśród znalezionych i odwrotnie
            'opposite_status' => (bool) env('MATCH_FILTER_OPPOSITE_STATUS', true),
            // Ten sam gatunek
            'same_species' => (bool) env('MATCH_FILTER_SAME_SPECIES', true),
            // Promień od współrzędnych zgłoszenia (km). 0 = bez limitu
            'radius_km' => (float) env('MATCH_FILTER_RADIUS_KM', 150),
            // ** Okno dat zdarzenia (± dni). 0 = wyłączone - domyślnie wyłączone, bo daty
            // w zgłoszeniach bywają niewiarygodne
            'date_window_days' => (int) env('MATCH_FILTER_DATE_WINDOW_DAYS', 0),
            // Pomijamy ogłoszenia z tym samym e-mailem autora (ta sama osoba, np. duplikat)
            'exclude_same_email' => (bool) env('MATCH_FILTER_EXCLUDE_SAME_EMAIL', true),
        ],

        // Limit czasu procesu Pythona (sam ranking, numpy)
        'timeout' => (int) env('MATCH_SEARCH_TIMEOUT', 60),
    ],

    // ---------------------------
    // Mail z kandydatami po dodaniu zgłoszenia (php artisan match:notify)
    // ---------------------------
    'notify' => [
        // ** Mail idzie dopiero po zatwierdzeniu zgłoszenia. Ile godzin OD ZATWIERDZENIA
        // zlecenie może czekać na wektory zdjęć (np. przy pauzie kolejki). Po tym czasie
        // wygasa bez maila - wiadomość po kilku dniach byłaby już myląca
        'ttl_hours' => (int) env('MATCH_NOTIFY_TTL_HOURS', 72),
        // ** Ile dni zlecenie czeka na moderację, zanim wygaśnie (zgłoszenie, którego nikt
        // nie zatwierdził ani nie odrzucił - żeby nie wisiało w kolejce w nieskończoność)
        'pending_ttl_days' => (int) env('MATCH_NOTIFY_PENDING_TTL_DAYS', 30),
        // Próby przy błędzie (Python / wysyłka maila), potem status "failed"
        'max_attempts' => 3,
        // Zleceń na jedno uruchomienie
        'batch' => 20,
        // ** Mail także do autorów STARYCH ogłoszeń z listy dopasowań ("pojawiło się nowe
        // podobne ogłoszenie"), patrz App\Services\MatchOwnerNotifier
        'owners_enabled' => (bool) env('MATCH_NOTIFY_OWNERS', true),
        // ** Najwyżej jeden taki mail na tyle dni dla jednego ogłoszenia - kolejne
        // dopasowania w tym czasie tylko trafiają do logu (match_owner_notices)
        'owners_cooldown_days' => (int) env('MATCH_NOTIFY_OWNERS_COOLDOWN_DAYS', 3),
    ],
];
