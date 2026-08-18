<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Noskiem.org — Znajdziemy go noskiem')</title>
        <meta name="description" content="@yield('description', 'Zaginął Ci zwierzak lub znalazłeś zwierzaka? Noskiem.pl to ogólnopolska baza zgłoszeń zaginionych, znalezionych i widzianych zwierząt. Znajdziemy go noskiem.')">

        {{-- Fonty (Inter, Barlow) hostowane lokalnie — patrz resources/css/fonts.css --}}

        {{-- Style/skrypty dociągane tylko przez strony, które ich potrzebują (np. Leaflet na
             stronach z mapą) — MUSI stać przed głównym @vite() niżej: kolejność <script
             type=module> w dokumencie decyduje o kolejności wykonania, a Alpine.data(...) z
             tych wpisów musi się zarejestrować przed Alpine.start() z app.js (patrz komentarz
             w resources/js/app.js) --}}
        @stack('head-assets')

        <!-- Skrypty i style -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data="{}" class="font-[Inter,ui-sans-serif,system-ui,sans-serif] antialiased text-[#283618] bg-white">
        @php
            // ---------------------------
            // Aktywna sekcja nawigacji — podświetlamy link do strony, na której użytkownik faktycznie jest
            // ---------------------------
            $navIsHome = request()->routeIs('home');
            $navIsLost = request()->routeIs('animals.index') && request('status') === 'lost';
            $navIsFound = request()->routeIs('animals.index') && request('status') === 'found';
            $navIsMap = request()->routeIs('map.index');
            $navIsCreate = request()->routeIs('animals.create');
        @endphp

        {{-- ---------------------------
             Navbar
             --------------------------- --}}
        <header class="sticky top-0 z-40 bg-white shadow-[0px_2px_10px_0px_rgba(30,38,18,0.05)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    {{-- Logo + nazwa marki --}}
                    <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                        <svg class="h-12 w-12 text-[#283618]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">
                                <!-- pinezka -->
                                <path d="M 120 212 C 120 212 55 140 55 94 C 55 56 84 28 120 28 C 156 28 185 56 185 94 C 185 140 120 212 120 212 Z"
                                        fill="none" stroke="#000" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
                                <!-- nos kota -->
                                <path d="M 104 74 L 136 74 C 142 74 144 80 140 85 L 126 101 C 123 105 117 105 114 101 L 100 85 C 96 80 98 74 104 74 Z" fill="#000"/>
                                <!-- pyszczek -->
                                <g fill="none" stroke="#000" stroke-width="8" stroke-linecap="round">
                                    <path d="M 120 104 L 120 112"/>
                                    <path d="M 120 112 C 120 122 110 126 102 121"/>
                                    <path d="M 120 112 C 120 122 130 126 138 121"/>
                                    <!-- wąsy -->
                                    <path d="M 70 78 L 90 82" stroke-width="7"/>
                                    <path d="M 72 98 L 91 95" stroke-width="7"/>
                                    <path d="M 170 78 L 150 82" stroke-width="7"/>
                                    <path d="M 168 98 L 149 95" stroke-width="7"/>
                                </g>
                            </svg>
                         <span class="font-[Barlow,ui-sans-serif,system-ui,sans-serif] text-[35px] text-[#283618]"><span class="font-semibold">noskiem</span>.org</span>
                    </a>
                    {{-- Linki nawigacyjne — widoczne tylko na desktopie, na mobile zastępuje je Bottom Nav --}}
                    <nav class="hidden md:flex items-center gap-10 text-[15px]">
                        <a href="{{ route('home') }}" class="{{ $navIsHome ? 'font-semibold text-[#283618]' : 'text-[#616657] transition-colors hover:text-[#283618] active:text-[#1e2812]' }}">Główna</a>
                        <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="{{ $navIsLost ? 'font-semibold text-[#283618]' : 'text-[#616657] transition-colors hover:text-[#283618] active:text-[#1e2812]' }}">Zaginione</a>
                        <a href="{{ route('animals.index', ['status' => 'found']) }}" class="{{ $navIsFound ? 'font-semibold text-[#283618]' : 'text-[#616657] transition-colors hover:text-[#283618] active:text-[#1e2812]' }}">Znalezione</a>
                        <a href="{{ route('map.index') }}" class="{{ $navIsMap ? 'font-semibold text-[#283618]' : 'text-[#616657] transition-colors hover:text-[#283618] active:text-[#1e2812]' }}">Mapa</a>
                        <a href="#" class="text-[#616657] transition-colors hover:text-[#283618] active:text-[#1e2812]">Jak to działa</a>
                    </nav>
                    {{-- Przycisk dodania ogłoszenia — status wg trybu wybranego na stronie głównej (jeśli aktywny) --}}
                    <a
                        href="{{ route('animals.create') }}"
                        :href="'{{ route('animals.create') }}?status=' + ($store.petMode === 'znalazlem' ? 'found' : 'lost')"
                        class="hidden sm:inline-flex items-center rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.97)] active:bg-[#161f0c]"
                    >
                        + Dodaj ogłoszenie
                    </a>
                </div>
            </div>
        </header>

        {{-- ---------------------------
             Komunikaty sesji (flash) — np. potwierdzenie wysłania wiadomości/zgłoszenia
             --------------------------- --}}
        @if (session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="rounded-xl bg-[#dbe9d8] px-4 py-3 text-[14px] text-[#3f6212]">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        {{-- ---------------------------
             Treść strony
             --------------------------- --}}
        <main class="pb-24 md:pb-0">
            @yield('content')
        </main>

        {{-- ---------------------------
             Footer
             --------------------------- --}}
        <footer class="bg-[#283618] text-[#fefae0]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Marka i slogan --}}
                    <div>
                        <span class="font-semibold text-[15px]">noskiem.org</span>
                        <p class="mt-3 text-[13px] text-[#c9cdb8]">„Znajdziemy go noskiem"</p>
                    </div>
                    {{-- Nawigacja --}}
                    <div>
                        <p class="font-semibold text-[13px] uppercase tracking-wide text-[#c9cdb8]">Nawigacja</p>
                        <ul class="mt-3 space-y-2 text-[14px]">
                            <li><a href="{{ route('animals.index') }}" class="transition-colors hover:underline active:text-[#c9cdb8]">Baza zwierząt</a></li>
                            <li><a href="{{ route('animals.create') }}" :href="'{{ route('animals.create') }}?status=' + ($store.petMode === 'znalazlem' ? 'found' : 'lost')" class="transition-colors hover:underline active:text-[#c9cdb8]">Dodaj ogłoszenie</a></li>
                            <li><a href="#" class="transition-colors hover:underline active:text-[#c9cdb8]">Jak to działa</a></li>
                            <li><a href="#" class="transition-colors hover:underline active:text-[#c9cdb8]">Blog</a></li>
                        </ul>
                    </div>
                    {{-- Informacje --}}
                    <div>
                        <p class="font-semibold text-[13px] uppercase tracking-wide text-[#c9cdb8]">Informacje</p>
                        <ul class="mt-3 space-y-2 text-[14px]">
                            <li><a href="/regulamin" class="transition-colors hover:underline active:text-[#c9cdb8]">Regulamin</a></li>
                            <li><a href="#" class="transition-colors hover:underline active:text-[#c9cdb8]">Polityka prywatności</a></li>
                            <li><a href="#" class="transition-colors hover:underline active:text-[#c9cdb8]">Kontakt</a></li>
                        </ul>
                    </div>
                    {{-- Kontakt / social media --}}
                    @php($contactEmail = \App\Models\Setting::get('contact_email', 'kontakt@noskiem.pl'))
                    <div>
                        <p class="font-semibold text-[13px] uppercase tracking-wide text-[#c9cdb8]">Kontakt</p>
                        <ul class="mt-3 space-y-2 text-[14px]">
                            <li><a href="mailto:{{ $contactEmail }}" class="hover:underline">{{ $contactEmail }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 border-t border-white/10 pt-6 text-[12px] text-[#c9cdb8]">
                    &copy; {{ date('Y') }} noskiem.org. Wszelkie prawa zastrzeżone.
                </div>
            </div>
        </footer>
        {{-- ---------------------------
             Bottom Nav (mobile) — widoczny tylko na urządzeniach mobilnych, zastępuje navbar
             --------------------------- --}}
        <nav class="md:hidden fixed inset-x-0 bottom-0 z-40 bg-white border-t border-[#e5e5dc] shadow-[0px_-2px_10px_0px_rgba(30,38,18,0.06)] will-change-transform">
            <div class="grid grid-cols-5 items-end h-16">
                {{-- Główna --}}
                <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $navIsHome ? 'font-semibold text-[#283618]' : 'text-[#616657]' }} transition active:transform-[scale(0.95)] active:text-[#1e2812]">
                    <span class="h-0.5 w-8 rounded-full {{ $navIsHome ? 'bg-[#283618]' : 'bg-transparent' }}"></span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 11.5 12 4l9 7.5"/>
                        <path d="M5 10v9a1 1 0 0 0 1 1h4v-5h4v5h4a1 1 0 0 0 1-1v-9"/>
                    </svg>
                    Główna
                </a>
                {{-- Zaginione --}}
                <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $navIsLost ? 'font-semibold text-[#283618]' : 'text-[#616657]' }} transition active:transform-[scale(0.95)] active:text-[#283618]">
                    <span class="h-0.5 w-8 rounded-full {{ $navIsLost ? 'bg-[#283618]' : 'bg-transparent' }}"></span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.5-3.5"/>
                    </svg>
                    Zaginione
                </a>
                @if ($navIsCreate)
                    {{-- Na formularzu nowego ogłoszenia ten sam, najbardziej "klikalny" przycisk
                         zamienia się w zapis formularza zamiast linku do /animals/create — użytkownicy
                         odruchowo w niego klikają, a link do tej samej strony powodował pełne
                         przeładowanie i utratę wpisanych danych. Walidacja/zapis to już sprawa formularza. --}}
                    <button
                        type="submit"
                        form="animal-create-form"
                        aria-label="Zapisz ogłoszenie"
                        class="flex flex-col items-center justify-center -translate-y-3 transition active:transform-[scale(0.90)]"
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#283618] text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.3)] transition active:bg-[#161f0c]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <path d="M17 21V13H7v8"/>
                                <path d="M7 3v5h8"/>
                            </svg>
                        </span>
                    </button>
                @else
                    {{-- Dodaj ogłoszenie — wyróżniony, centralny przycisk --}}
                    <a
                        href="{{ route('animals.create') }}"
                        :href="'{{ route('animals.create') }}?status=' + ($store.petMode === 'znalazlem' ? 'found' : 'lost')"
                        aria-label="Dodaj ogłoszenie"
                        class="flex flex-col items-center justify-center -translate-y-3 transition active:transform-[scale(0.90)]"
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#283618] text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.3)] transition active:bg-[#161f0c]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </span>
                    </a>
                @endif
                {{-- Znalezione --}}
                <a href="{{ route('animals.index', ['status' => 'found']) }}" class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $navIsFound ? 'font-semibold text-[#283618]' : 'text-[#616657]' }} transition active:transform-[scale(0.95)] active:text-[#283618]">
                    <span class="h-0.5 w-8 rounded-full {{ $navIsFound ? 'bg-[#283618]' : 'bg-transparent' }}"></span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"/>
                    </svg>
                    Znalezione
                </a>
                {{-- Mapa --}}
                <a href="{{ route('map.index') }}" class="flex flex-col items-center justify-center gap-1 text-[11px] {{ $navIsMap ? 'font-semibold text-[#283618]' : 'text-[#616657]' }} transition active:transform-[scale(0.95)] active:text-[#283618]">
                    <span class="h-0.5 w-8 rounded-full {{ $navIsMap ? 'bg-[#283618]' : 'bg-transparent' }}"></span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linejoin="round" d="M9 4 4 6v14l5-2 6 2 5-2V4l-5 2-6-2Z"/>
                        <path d="M9 4v14M15 6v14"/>
                    </svg>
                    Mapa
                </a>
            </div>
        </nav>

        {{-- ---------------------------
             Baner zgody na ciasteczka — widoczny do momentu akceptacji, potem
             flaga trzymana w sesji (nie w osobnym ciasteczku), więc obowiązuje
             tylko do końca bieżącej sesji przeglądarki
             --------------------------- --}}
        @unless (session('cookie_consent'))
            <div
                x-data="{ visible: true, sending: false }"
                x-show="visible"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-x-0 bottom-16 z-50 border-t border-[#e5e5dc] bg-white px-4 py-4 shadow-[0px_-2px_10px_0px_rgba(30,38,18,0.08)] sm:px-6 md:bottom-0 lg:px-8"
            >
                <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 md:flex-row md:justify-between">
                    <p class="text-center text-[13px] text-[#616657] md:text-left">
                        Używamy ciasteczek niezbędnych do działania strony (m.in. utrzymania sesji i ochrony formularzy przed nadużyciami).
                    </p>
                    <div class="flex shrink-0 flex-wrap items-center justify-center gap-3">
                        {{-- Ukryte na razie — nie mamy jeszcze żadnych opcjonalnych ciasteczek,
                             więc "wszystkie" i "wybrane" nie mają dziś sensu. Przywrócić, gdy
                             pojawi się pierwsza kategoria ciasteczek opcjonalnych. --}}
                        {{--
                        <form action="{{ route('cookies.accept') }}" method="POST">
                            @csrf
                            <input type="hidden" name="level" value="all">
                            <button
                                type="submit"
                                class="rounded-xl bg-[#283618] px-5 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] hover:bg-[#1e2812] transition-colors"
                            >
                                Zaakceptuj wszystkie
                            </button>
                        </form>
                        --}}
                        {{-- ** Wysyłka przez fetch, żeby zaakceptowanie ciasteczek nie przeładowywało całej strony
                             (traci scroll, flash treści) — bez JS formularz nadal działa zwyczajnie (POST + redirect) --}}
                        <form
                            action="{{ route('cookies.accept') }}"
                            method="POST"
                            @submit.prevent="
                                sending = true;
                                fetch($el.action, {
                                    method: 'POST',
                                    headers: { Accept: 'application/json' },
                                    body: new FormData($el),
                                }).finally(() => { visible = false; sending = false; });
                            "
                        >
                            @csrf
                            <input type="hidden" name="level" value="necessary">
                            <button
                                type="submit"
                                :disabled="sending"
                                class="cursor-pointer rounded-xl border border-[#283618] bg-white px-5 py-2.5 text-[13px] font-semibold text-[#283618] transition hover:bg-[#283618] hover:text-[#fefae0] active:transform-[scale(0.97)] active:bg-[#1e2812] disabled:cursor-wait disabled:opacity-70"
                            >
                                Zaakceptuj tylko niezbędne
                            </button>
                        </form>
                        {{--
                        <button
                            type="button"
                            disabled
                            title="Obecnie nie używamy opcjonalnych ciasteczek"
                            class="cursor-not-allowed rounded-xl border border-[#e5e5dc] bg-white px-5 py-2.5 text-[13px] font-semibold text-[#a3a795]"
                        >
                            Zaakceptuj wybrane
                        </button>
                        --}}
                        <a
                            href="{{ route('pages.show', 'cookies') }}"
                            class="rounded-xl border border-[#c3d6bf] bg-[#dbe9d8] px-5 py-2.5 text-[13px] font-semibold text-[#283618] transition hover:bg-[#c9dec4] active:transform-[scale(0.97)] active:bg-[#bcd4b6]"
                            target="_blank"
                            >
                            Informacje o ciasteczkach
                        </a>
                    </div>
                </div>
            </div>
        @endunless
    </body>
</html>
