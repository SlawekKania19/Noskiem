<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Noskiem.pl — Znajdziemy go noskiem')</title>
        
        <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=barlow:400,600,900" rel="stylesheet" />

        <!-- Skrypty i style -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data="{}" class="font-['Inter'] antialiased text-[#283618] bg-white">
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
                         <span class="font-['Barlow'] text-[35px] text-[#283618]"><span class="font-semibold">noskiem</span>.org</span>
                    </a>
                    {{-- Linki nawigacyjne — widoczne tylko na desktopie, na mobile zastępuje je Bottom Nav --}}
                    <nav class="hidden md:flex items-center gap-10 text-[15px]">
                        <a href="{{ route('home') }}" class="font-semibold text-[#283618]">Główna</a>
                        <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="text-[#616657] hover:text-[#283618] transition-colors">Zaginione</a>
                        <a href="{{ route('animals.index', ['status' => 'found']) }}" class="text-[#616657] hover:text-[#283618] transition-colors">Znalezione</a>
                        <a href="#" class="text-[#616657] hover:text-[#283618] transition-colors">Jak to działa</a>
                    </nav>
                    {{-- Przycisk dodania ogłoszenia — status wg trybu wybranego na stronie głównej (jeśli aktywny) --}}
                    <a
                        href="{{ route('animals.create') }}"
                        :href="'{{ route('animals.create') }}?status=' + ($store.petMode === 'znalazlem' ? 'found' : 'lost')"
                        class="hidden sm:inline-flex items-center rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] hover:bg-[#1e2812] transition-colors"
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
                            <li><a href="{{ route('animals.index') }}" class="hover:underline">Baza zwierząt</a></li>
                            <li><a href="{{ route('animals.create') }}" :href="'{{ route('animals.create') }}?status=' + ($store.petMode === 'znalazlem' ? 'found' : 'lost')" class="hover:underline">Dodaj ogłoszenie</a></li>
                            <li><a href="#" class="hover:underline">Jak to działa</a></li>
                            <li><a href="#" class="hover:underline">Blog</a></li>
                        </ul>
                    </div>
                    {{-- Informacje --}}
                    <div>
                        <p class="font-semibold text-[13px] uppercase tracking-wide text-[#c9cdb8]">Informacje</p>
                        <ul class="mt-3 space-y-2 text-[14px]">
                            <li><a href="/regulamin" class="hover:underline">Regulamin</a></li>
                            <li><a href="#" class="hover:underline">Polityka prywatności</a></li>
                            <li><a href="#" class="hover:underline">Kontakt</a></li>
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
        <nav class="md:hidden fixed inset-x-0 bottom-0 z-40 bg-white border-t border-[#e5e5dc] shadow-[0px_-2px_10px_0px_rgba(30,38,18,0.06)]">
            <div class="grid grid-cols-5 items-end h-16">
                {{-- Główna --}}
                <a href="{{ route('animals.index') }}" class="flex flex-col items-center justify-center gap-1 text-[11px] text-[#283618]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 11.5 12 4l9 7.5"/>
                        <path d="M5 10v9a1 1 0 0 0 1 1h4v-5h4v5h4a1 1 0 0 0 1-1v-9"/>
                    </svg>
                    Główna
                </a>
                {{-- Zaginione --}}
                <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="flex flex-col items-center justify-center gap-1 text-[11px] text-[#616657]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.5-3.5"/>
                    </svg>
                    Zaginione
                </a>
                {{-- Dodaj ogłoszenie — wyróżniony, centralny przycisk --}}
                <a
                    href="{{ route('animals.create') }}"
                    :href="'{{ route('animals.create') }}?status=' + ($store.petMode === 'znalazlem' ? 'found' : 'lost')"
                    class="flex flex-col items-center justify-center -translate-y-3"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#283618] text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.3)]">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>
                </a>
                {{-- Znalezione --}}
                <a href="{{ route('animals.index', ['status' => 'found']) }}" class="flex flex-col items-center justify-center gap-1 text-[11px] text-[#616657]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"/>
                    </svg>
                    Znalezione
                </a>
                {{-- Więcej --}}
                <a href="#" class="flex flex-col items-center justify-center gap-1 text-[11px] text-[#616657]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="5" cy="12" r="1.5"/>
                        <circle cx="12" cy="12" r="1.5"/>
                        <circle cx="19" cy="12" r="1.5"/>
                    </svg>
                    Więcej
                </a>
            </div>
        </nav>

        {{-- ---------------------------
             Baner zgody na ciasteczka — widoczny do momentu akceptacji, potem
             flaga trzymana w sesji (nie w osobnym ciasteczku), więc obowiązuje
             tylko do końca bieżącej sesji przeglądarki
             --------------------------- --}}
        @unless (session('cookie_consent'))
            <div class="fixed inset-x-0 bottom-16 z-50 border-t border-[#e5e5dc] bg-white px-4 py-4 shadow-[0px_-2px_10px_0px_rgba(30,38,18,0.08)] sm:px-6 md:bottom-0 lg:px-8">
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
                        <form action="{{ route('cookies.accept') }}" method="POST">
                            @csrf
                            <input type="hidden" name="level" value="necessary">
                            <button
                                type="submit"
                                class="rounded-xl border border-[#283618] bg-white px-5 py-2.5 text-[13px] font-semibold text-[#283618] hover:bg-[#283618] hover:text-[#fefae0] transition-colors"
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
                            class="rounded-xl border border-[#c3d6bf] bg-[#dbe9d8] px-5 py-2.5 text-[13px] font-semibold text-[#283618] hover:bg-[#c9dec4] transition-colors"
                        >
                            Informacje o ciasteczkach
                        </a>
                    </div>
                </div>
            </div>
        @endunless
    </body>
</html>
