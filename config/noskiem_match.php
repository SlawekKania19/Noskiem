<?php

// ---------------------------
// Dopasowania po zdjęciach (moduł Pythona noskiem_match/).
// Wszystko regulowane z .env — zmiana działa od następnego uruchomienia workera
// (o ile config nie jest zcache'owany; deploy robi optimize:clear).
// Ustawienia modelu (nazwa, wersja, kadrowanie) są w noskiem_match/config.json —
// tam jest jedno źródło model_version, czytane też przez PHP.
// ---------------------------

return [

    // ** Główny włącznik. Domyślnie wyłączony — bez zainstalowanego środowiska
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
    // więc pauza / przesunięcie w czasie niczego nie gubi — zdjęcia po prostu czekają.
    // ---------------------------
    'queue' => [

        // ** Wstrzymanie przetwarzania (np. przy dużym ruchu) — kolejka rośnie, nic nie przepada
        'paused' => (bool) env('MATCH_PAUSED', false),

        // ** Okno godzin, w którym worker może pracować, np. "22:00-06:00" (może przechodzić
        // przez północ). Puste = całą dobę
        'hours' => env('MATCH_HOURS', ''),
        'timezone' => env('MATCH_TIMEZONE', 'Europe/Warsaw'),

        // ** Priorytet CPU procesu Pythona (nice 0–19; wyżej = niższy priorytet) —
        // przy obciążeniu strona WWW dostaje CPU pierwsza
        'nice' => (int) env('MATCH_NICE', 10),

        // ** Wątki torcha. Hosting (CloudLinux) pokazuje 32 rdzenie serwera, a konto ma limit —
        // bez jawnej wartości torch odpaliłby 32 wątki. Pomiar QA: 1 wątek ~2,7 s/zdjęcie,
        // 2 wątki ~1,7 s/zdjęcie
        'threads' => (int) env('MATCH_THREADS', 1),

        // ** Maks. zdjęć na jedno uruchomienie i limit czasu — worker kończy po bieżącym
        // zdjęciu, reszta idzie w następnym uruchomieniu (scheduler co minutę)
        'batch_limit' => (int) env('MATCH_BATCH_LIMIT', 30),
        'max_seconds' => (int) env('MATCH_MAX_SECONDS', 240),

        // ** Po pierwszym pobraniu wag z Hugging Face: true = bez odpytywania sieci przy
        // każdym starcie (pomiar QA: ładowanie modeli 2,4 s zamiast 12 s, odporne na awarię HF)
        'hf_offline' => (bool) env('MATCH_HF_OFFLINE', false),
    ],
];
