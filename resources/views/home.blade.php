@php
    // ---------------------------
    // Teksty Hero i inne treści edytowalne z panelu Filament (App\Filament\Pages\Settings)
    // ---------------------------
    $heroHeadlineLost = \App\Models\Setting::get('hero_headline_lost', 'Zaginął Ci pupil? Znajdziemy go noskiem.');
    $heroHeadlineFound = \App\Models\Setting::get('hero_headline_found', 'Znalazłeś zwierzaka? Pomóż mu wrócić do domu.');
    $heroDescriptionLost = \App\Models\Setting::get('hero_description_lost', 'Przeszukaj bazę znalezionych i widzianych zwierząt z całej Polski, zanim dodasz własne ogłoszenie.');
    $heroDescriptionFound = \App\Models\Setting::get('hero_description_found', 'Dodaj ogłoszenie o znalezionym zwierzaku, żeby jak najszybciej trafiło do właściciela.');
@endphp

@extends('layouts.public')

@section('title', 'Noskiem.org - Znajdziemy go noskiem')

@section('content')

    {{-- ---------------------------
         "Pokazuj częściej z mojej okolicy" (sekcja z losowanymi ogłoszeniami).
         Przeglądarka pyta o zgodę na lokalizację; zapisujemy ją ZAOKRĄGLONĄ do 0,1 stopnia
         (ok. 10 km) w ciasteczku noskiem_loc na 30 dni i przeładowujemy stronę - serwer
         podbija wagi pobliskich ogłoszeń (App\Services\HomepageListings).
         Zwykły skrypt, nie moduł - musi istnieć, zanim wystartuje Alpine.
         --------------------------- --}}
    <script>
        window.homeLocation = () => ({
            busy: false,
            error: '',

            enable() {
                if (!navigator.geolocation) {
                    this.error = 'Ta przeglądarka nie udostępnia lokalizacji.';
                    return;
                }

                this.busy = true;
                this.error = '';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const value = position.coords.latitude.toFixed(1) + '_' + position.coords.longitude.toFixed(1);
                        document.cookie = 'noskiem_loc=' + value + '; path=/; max-age=2592000; SameSite=Lax';
                        this.reload();
                    },
                    () => {
                        this.busy = false;
                        this.error = 'Nie udało się ustalić lokalizacji - sprawdź, czy przeglądarka ma na to zgodę.';
                    },
                    { maximumAge: 600000, timeout: 10000 }
                );
            },

            disable() {
                document.cookie = 'noskiem_loc=; path=/; max-age=0; SameSite=Lax';
                this.reload();
            },

            // ** Po przeładowaniu wracamy do tego samego trybu (Szukam / Znalazłem) -
            // "mode" pochodzi z nadrzędnej sekcji Hero
            reload() {
                try { sessionStorage.setItem('homeMode', this.mode); } catch (e) {}
                window.location.reload();
            },
        });
    </script>

    {{-- ---------------------------
         Hero Section
         Stan "Szukam" jest domyślny, przełącznik zmienia treść bez przeładowania strony
         --------------------------- --}}
    {{-- ** x-init: przywraca tryb zapamiętany przed przeładowaniem strony (homeLocation.reload) --}}
    <section
        x-data="{ mode: 'szukam' }"
        x-init="try { const m = sessionStorage.getItem('homeMode'); if (m) { sessionStorage.removeItem('homeMode'); mode = m; $store.petMode = m; } } catch (e) {}"
        class="bg-[#FFFFFF]"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
            {{-- ** Switcher Szukam / Znalazłem (Figma: node 22:229) --}}
            <div class="inline-flex items-center rounded-full bg-white p-1 shadow-[0px_2px_10px_0px_rgba(30,38,18,0.08)]">
                <button
                    type="button"
                    @click="mode = 'szukam'; $store.petMode = 'szukam'"
                    :class="mode === 'szukam' ? 'bg-[#283618] text-[#fefae0] hover:bg-[#1e2812]' : 'text-[#616657] hover:bg-[#f4f4ef] hover:text-[#283618]'"
                    class="cursor-pointer rounded-full px-6 py-2 text-[14px] font-semibold transition active:transform-[scale(0.97)]"
                >
                    Szukam zwierzaka
                </button>
                <button
                    type="button"
                    @click="mode = 'znalazlem'; $store.petMode = 'znalazlem'"
                    :class="mode === 'znalazlem' ? 'bg-[#283618] text-[#fefae0] hover:bg-[#1e2812]' : 'text-[#616657] hover:bg-[#f4f4ef] hover:text-[#283618]'"
                    class="cursor-pointer rounded-full px-6 py-2 text-[14px] font-semibold transition active:transform-[scale(0.97)]"
                >
                    Znalazłem zwierzaka
                </button>
            </div>
            {{-- ** Nagłówek i opis - treść zależna od wybranego trybu --}}
            {{-- ** Wariant "szukam" bez x-cloak - to domyślny stan `mode`, więc jest widoczny
                 od pierwszego renderu (bez Alpine); dzięki temu h1/p nie startują puste i nie
                 "wyskakują" na pełną wysokość dopiero po hydratacji (przyczyna złego CLS) --}}
            <h1 class="mt-8 text-[32px] sm:text-[44px] font-semibold leading-tight text-[#283618]">
                <span x-show="mode === 'szukam'">{{ $heroHeadlineLost }}</span>
                <span x-show="mode === 'znalazlem'" x-cloak>{{ $heroHeadlineFound }}</span>
            </h1>

            <p class="mt-4 max-w-2xl mx-auto text-[15px] sm:text-[16px] text-[#616657]">
                <span x-show="mode === 'szukam'">{{ $heroDescriptionLost }}</span>
                <span x-show="mode === 'znalazlem'" x-cloak>{{ $heroDescriptionFound }}</span>
            </p>

            {{-- ** Wyszukiwarka - grid z kolumnami [pole, przycisk], żeby podpowiedź pod spodem dało się wyśrodkować dokładnie względem pola --}}
            <form action="{{ route('animals.index') }}" method="GET" class="mt-8 mx-auto grid max-w-xl grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                <input
                    type="text"
                    name="q"
                    placeholder="Wpisz miasto, rasę lub imię zwierzaka..."
                    class="sm:col-start-1 sm:row-start-1 rounded-xl border border-[#e5e5dc] px-5 py-3 text-[14px] text-[#283618] placeholder:text-[#a3a795] focus:border-[#283618] focus:outline-hidden"
                >
                <button
                    type="submit"
                    class="sm:col-start-2 sm:row-start-1 cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] hover:bg-[#1e2812] transition active:transform-[scale(0.98)] active:bg-[#161f0c]"
                >
                    Szukaj
                </button>

                {{-- ** Podpowiedź / CTA - w tej samej kolumnie co pole, więc wyśrodkowane względem niego, nie całego wiersza --}}
                <p class="sm:col-start-1 sm:row-start-2 text-center text-[13px] text-[#616657]" x-show="mode === 'znalazlem'" x-cloak>
                    Najpierw poszukaj, zanim dodasz ogłoszenie - być może ktoś już szuka tego zwierzaka.
                </p>
                <a
                    href="{{ route('animals.create', ['status' => 'lost']) }}"
                    class="sm:col-start-1 sm:row-start-2 inline-flex items-center justify-center rounded-xl border border-[#c3d6bf] bg-[#dbe9d8] px-6 py-2.5 text-[13px] font-semibold text-[#283618] hover:bg-[#c9dec4] transition active:transform-[scale(0.98)] active:bg-[#bcd4b6]"
                    x-show="mode === 'szukam'"
                    x-cloak
                >
                    Dodaj ogłoszenie o zaginionym zwierzaku
                </a>
            </form>
        </div>
        {{-- ---------------------------
             Ogłoszenia - w trybie "Znalazłem" pokazujemy poszukiwane (lost), w trybie "Szukam"
             znalezione (found). Każdy status ma dwie grupy, patrz home/listings.blade.php
             --------------------------- --}}
        <div x-show="mode === 'znalazlem'" x-cloak class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            @include('home.listings', [
                'status' => 'lost',
                'title' => 'Poszukiwane',
                'rotatedTitle' => 'Wciąż szukamy',
                'emptyText' => 'Brak zaginionych zwierząt w bazie.',
                'latest' => $sections['lost']['latest'],
                'rotated' => $sections['lost']['rotated'],
            ])
        </div>

        <div x-show="mode === 'szukam'" x-cloak class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            @include('home.listings', [
                'status' => 'found',
                'title' => 'Widziane',
                'rotatedTitle' => 'Wciąż nie znaleźliśmy właściciela',
                'emptyText' => 'Brak znalezionych zwierząt w bazie.',
                'latest' => $sections['found']['latest'],
                'rotated' => $sections['found']['rotated'],
            ])
        </div>
    </section>

@endsection
