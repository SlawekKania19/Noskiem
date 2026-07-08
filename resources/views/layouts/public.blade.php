<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Noskiem.pl — Znajdziemy go noskiem')</title>
        
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Skrypty i style -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-['Inter'] antialiased text-[#283618] bg-white">
        {{-- ---------------------------
             Navbar 
             --------------------------- --}}
        <header class="sticky top-0 z-40 bg-white shadow-[0px_2px_10px_0px_rgba(30,38,18,0.05)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    {{-- Logo + nazwa marki --}}
                    <a href="{{ route('animals.index') }}" class="flex items-center gap-2 shrink-0">
                        <svg class="h-8 w-8 text-[#283618]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 14c-3.3 0-6 2.2-6 5v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-1c0-2.8-2.7-5-6-5Z"/>
                            <ellipse cx="4.5" cy="10" rx="2" ry="2.5"/>
                            <ellipse cx="19.5" cy="10" rx="2" ry="2.5"/>
                            <ellipse cx="8" cy="6" rx="2" ry="2.5"/>
                            <ellipse cx="16" cy="6" rx="2" ry="2.5"/>
                        </svg>
                        <span class="font-semibold text-[15px] text-[#283618]">noskiem.org</span>
                    </a>
                    {{-- Linki nawigacyjne — widoczne tylko na desktopie, na mobile zastępuje je Bottom Nav --}}
                    <nav class="hidden md:flex items-center gap-10 text-[15px]">
                        <a href="{{ route('animals.index') }}" class="font-semibold text-[#283618]">Główna</a>
                        <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="text-[#616657] hover:text-[#283618] transition-colors">Zaginione</a>
                        <a href="{{ route('animals.index', ['status' => 'found']) }}" class="text-[#616657] hover:text-[#283618] transition-colors">Znalezione</a>
                        <a href="#" class="text-[#616657] hover:text-[#283618] transition-colors">Jak to działa</a>
                    </nav>
                    {{-- Przycisk dodania ogłoszenia --}}
                    <a
                        href="{{ route('animals.create') }}"
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
                            <li><a href="{{ route('animals.create') }}" class="hover:underline">Dodaj ogłoszenie</a></li>
                            <li><a href="#" class="hover:underline">Jak to działa</a></li>
                            <li><a href="#" class="hover:underline">Blog</a></li>
                        </ul>
                    </div>
                    {{-- Informacje --}}
                    <div>
                        <p class="font-semibold text-[13px] uppercase tracking-wide text-[#c9cdb8]">Informacje</p>
                        <ul class="mt-3 space-y-2 text-[14px]">
                            <li><a href="#" class="hover:underline">Regulamin</a></li>
                            <li><a href="#" class="hover:underline">Polityka prywatności</a></li>
                            <li><a href="#" class="hover:underline">Kontakt</a></li>
                        </ul>
                    </div>
                    {{-- Kontakt / social media --}}
                    <div>
                        <p class="font-semibold text-[13px] uppercase tracking-wide text-[#c9cdb8]">Kontakt</p>
                        <ul class="mt-3 space-y-2 text-[14px]">
                            <li><a href="mailto:kontakt@noskiem.pl" class="hover:underline">kontakt@noskiem.pl</a></li>
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
                <a href="{{ route('animals.create') }}" class="flex flex-col items-center justify-center -translate-y-3">
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
    </body>
</html>
