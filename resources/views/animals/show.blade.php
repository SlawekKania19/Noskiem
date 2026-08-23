@php
    // ---------------------------
    // Dane pomocnicze dla widoku szczegółów ogłoszenia
    // ---------------------------
    $statusLabels = ['lost' => 'Zaginiony', 'found' => 'Znaleziony'];
    $statusStyles = [
        'lost' => 'bg-[#fcecd1] text-[#994d0a]',
        'found' => 'bg-[#dbe9d8] text-[#3f6212]',
    ];
    $statusLabel = $statusLabels[$animal->status] ?? $animal->status;
    $statusStyle = $statusStyles[$animal->status] ?? 'bg-[#eee] text-[#616657]';

    $cardTitle = $animal->title;
    $locationLabel = $animal->location_text ?: ($animal->city->name_pl ?? null);

    // ** Zdjęcia — główne na pierwszym miejscu
    $photos = $animal->photos->sortByDesc('is_main')->values();
@endphp

@extends('layouts.public')

@section('title', $cardTitle.' — Noskiem.pl')

@push('head-assets')
    @vite(['resources/css/leaflet.css', 'resources/js/maps.js'])
@endpush

@section('content')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- ---------------------------
             Nagłówek ogłoszenia
             --------------------------- --}}
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex rounded-md px-2 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusStyle }}">
                {{ $statusLabel }}
            </span>
            @if ($animal->species)
                <span class="text-[13px] text-[#616657]">{{ $animal->species->name_pl }}</span>
            @endif
            @if ($animal->breed)
                <span class="text-[13px] text-[#616657]">· {{ $animal->breed->breed_pl }}</span>
            @endif
        </div>

        <h1 class="mt-2 text-[28px] font-semibold text-[#283618]">{{ $cardTitle }}</h1>

        @if ($locationLabel)
            <p class="mt-1 text-[14px] text-[#8f9485]">📍 {{ $locationLabel }}</p>
        @endif

        {{-- ---------------------------
             Galeria zdjęć (tabela photos) — miniatury kwadratowe (mniej agresywny crop niż
             stały h-40/h-48 przy zmiennej liczbie kolumn), klik otwiera lightbox z pełnym zdjęciem
             (object-contain, bez obcinania) i nawigacją strzałkami
             --------------------------- --}}
        <div
            class="mt-6"
            x-data="{
                open: false,
                index: 0,
                photos: @js($photos->map(fn ($photo) => asset('storage/'.$photo->path))->values()),
                show(i) { this.index = i; this.open = true },
                next() { this.index = (this.index + 1) % this.photos.length },
                prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length },
            }"
        >
            @if ($photos->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($photos as $index => $photo)
                        <button
                            type="button"
                            @click="show({{ $index }})"
                            class="aspect-square cursor-zoom-in overflow-hidden rounded-2xl"
                        >
                            <img
                                src="{{ asset('storage/'.$photo->path) }}"
                                alt="{{ $cardTitle }}"
                                class="h-full w-full object-cover transition hover:scale-105"
                                loading="lazy"
                            >
                        </button>
                    @endforeach
                </div>

                {{-- ** Lightbox — z-[1100], bo mapa Leaflet niżej na stronie używa z-index do 1000 --}}
                <div
                    x-show="open"
                    x-cloak
                    @click.self="open = false"
                    @keydown.escape.window="open = false"
                    @keydown.arrow-right.window="next()"
                    @keydown.arrow-left.window="prev()"
                    class="fixed inset-0 z-[1100] flex items-center justify-center bg-black/90 p-4"
                >
                    <button
                        type="button"
                        @click="open = false"
                        class="absolute right-4 top-4 cursor-pointer text-[28px] text-white/80 hover:text-white"
                        aria-label="Zamknij podgląd"
                    >&times;</button>

                    <button
                        type="button"
                        x-show="photos.length > 1"
                        @click="prev()"
                        class="absolute left-2 cursor-pointer p-2 text-[32px] text-white/80 hover:text-white sm:left-4"
                        aria-label="Poprzednie zdjęcie"
                    >&lsaquo;</button>

                    <img
                        :src="photos[index]"
                        alt="{{ $cardTitle }}"
                        class="max-h-[85vh] max-w-full rounded-xl object-contain"
                    >

                    <button
                        type="button"
                        x-show="photos.length > 1"
                        @click="next()"
                        class="absolute right-2 cursor-pointer p-2 text-[32px] text-white/80 hover:text-white sm:right-4"
                        aria-label="Następne zdjęcie"
                    >&rsaquo;</button>
                </div>
            @else
                <div class="flex h-56 items-center justify-center rounded-2xl bg-[#dbe3d1] text-[40px]">
                    🐾
                </div>
            @endif
        </div>

        {{-- ---------------------------
             Mapa lokalizacji (Figma: node 22:238) — tylko do odczytu, pinezka nieprzesuwalna
             --------------------------- --}}
        @if ($animal->latitude && $animal->longitude)
            <div class="mt-6">
                <h2 class="text-[16px] font-semibold text-[#283618]">Lokalizacja</h2>
                <div
                    id="animal-map"
                    class="isolate mt-2 h-72 w-full overflow-hidden rounded-2xl"
                ></div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.initAnimalMap('animal-map', {{ $animal->latitude }}, {{ $animal->longitude }});
                });
            </script>
        @endif

        {{-- ** Pasek informujący o zgłoszeniach "też widziałem" — widoczny tylko jeśli
             takie istnieją, żeby ktoś przewijający stronę wiedział, że warto zjechać
             niżej (patrz kotwica #sightings przy timeline) --}}
        @if ($animal->status === 'found' && $animal->sightings->isNotEmpty())
            <a
                href="#sightings"
                class="mt-4 flex items-center justify-between gap-2 rounded-xl bg-[#dbe9d8] px-4 py-3 text-[13px] font-medium text-[#3f6212] transition hover:bg-[#cde0c8]"
            >
                <span>👀 Inni użytkownicy też zgłosili, że widzieli tego zwierzaka</span>
                <span>Zobacz &darr;</span>
            </a>
        @endif

        <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-3">

            {{-- ---------------------------
                 Opis, data zdarzenia, znaki szczególne
                 --------------------------- --}}
            <div class="lg:col-span-2">
                {{-- ** Szczegóły — powtórzenie gatunku/rasy/lokalizacji z nagłówka + kolory, żeby
                     były widoczne też w treści, a nie tylko w skróconej formie nad zdjęciami --}}
                <h2 class="text-[16px] font-semibold text-[#283618]">Szczegóły</h2>
                <dl class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @if ($animal->species)
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Gatunek</dt>
                            <dd class="mt-1 text-[14px] text-[#283618]">{{ $animal->species->name_pl }}</dd>
                        </div>
                    @endif
                    @if ($animal->breed)
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Rasa</dt>
                            <dd class="mt-1 text-[14px] text-[#283618]">{{ $animal->breed->breed_pl }}</dd>
                        </div>
                    @endif
                    @if ($locationLabel)
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Lokalizacja</dt>
                            <dd class="mt-1 text-[14px] text-[#283618]">{{ $locationLabel }}</dd>
                        </div>
                    @endif
                    @if ($animal->city)
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Miejscowość</dt>
                            <dd class="mt-1 text-[14px] text-[#283618]">{{ $animal->city->name_pl }}</dd>
                        </div>
                    @endif
                    @if ($animal->voivodeship)
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Województwo</dt>
                            <dd class="mt-1 text-[14px] text-[#283618]">{{ $animal->voivodeship->name_pl }}</dd>
                        </div>
                    @endif
                    @if ($animal->colors->isNotEmpty())
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Kolory</dt>
                            <dd class="mt-1 text-[14px] text-[#283618]">{{ $animal->colors->pluck('name')->implode(', ') }}</dd>
                        </div>
                    @endif
                </dl>

                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Opis</h2>
                <p class="mt-2 whitespace-pre-line text-[14px] leading-relaxed text-[#616657]">{{ $animal->description }}</p>

                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Data zdarzenia</h2>
                <p class="mt-2 text-[14px] text-[#616657]">{{ $animal->date_event?->locale('pl')->translatedFormat('d F Y') }}</p>

                @if ($animal->ident_marks)
                    <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Znaki szczególne</h2>
                    <p class="mt-2 whitespace-pre-line text-[14px] leading-relaxed text-[#616657]">{{ $animal->ident_marks }}</p>
                @endif

                @if ($animal->behavior)
                    <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Zachowanie</h2>
                    <p class="mt-2 whitespace-pre-line text-[14px] leading-relaxed text-[#616657]">{{ $animal->behavior }}</p>
                @endif
            </div>

            {{-- ---------------------------
                 Kontakt do zgłaszającego
                 --------------------------- --}}
            <div x-data="{ showPhone: false }" class="rounded-2xl border border-[#e5e5dc] p-5 lg:self-start">
                <h2 class="text-[16px] font-semibold text-[#283618]">Kontakt</h2>

                {{-- ** Telefon ukryty za przyciskiem (ochrona przed botami) --}}
                @if ($animal->contact_phone)
                    <div class="mt-4">
                        <p class="text-[12px] uppercase tracking-wide text-[#8f9485]">Telefon</p>
                        <button
                            type="button"
                            @click="showPhone = true"
                            x-show="!showPhone"
                            class="mt-1 cursor-pointer rounded-xl border border-[#283618] px-4 py-2 text-[13px] font-semibold text-[#283618] transition hover:bg-[#283618] hover:text-[#fefae0] active:transform-[scale(0.97)] active:bg-[#1e2812]"
                        >
                            Pokaż numer
                        </button>
                        <p x-show="showPhone" x-cloak class="mt-1 text-[15px] font-semibold text-[#283618]">
                            {{ $animal->formatted_phone }}
                        </p>
                    </div>
                @endif

                {{-- ** Zgłoszenie "też widziałem" — tylko dla ogłoszeń "Znaleziony"; dla
                     "Zaginiony" formularz kontaktowy poniżej wystarcza (patrz SightingController) --}}
                @if ($animal->status === 'found')
                    <a
                        href="{{ route('sightings.create', $animal) }}"
                        class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.97)] active:bg-[#161f0c]"
                    >
                        Zgłoś, że też widziałem/widziałam
                    </a>
                @endif

                {{-- ** Formularz "napisz wiadomość" — zapis do tabeli messages --}}
                <div class="mt-6">
                    @include('animals.partials.contact-form', ['action' => route('messages.store', $animal)])
                </div>
            </div>
        </div>

        {{-- ---------------------------
             Timeline "też widziałem" — pełna szerokość pod ogłoszeniem (nie w kolumnie
             głównej), tylko dla "Znaleziony" (patrz SightingController), zatwierdzone
             zgłoszenia od najstarszego
             --------------------------- --}}
        @if ($animal->status === 'found' && $animal->sightings->isNotEmpty())
            <div id="sightings" class="mt-10 scroll-mt-24">
                <h2 class="text-[16px] font-semibold text-[#283618]">Też widzieli</h2>
                <div class="mt-3 space-y-4">
                    @foreach ($animal->sightings as $sighting)
                        <div
                            x-data="{
                                contactOpen: false,
                                lightboxOpen: false,
                                lightboxIndex: 0,
                                lightboxPhotos: @js($sighting->photos->map(fn ($photo) => asset('storage/'.$photo->path))->values()),
                                showLightbox(i) { this.lightboxIndex = i; this.lightboxOpen = true },
                                nextPhoto() { this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxPhotos.length },
                                prevPhoto() { this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxPhotos.length) % this.lightboxPhotos.length },
                            }"
                            class="rounded-2xl border border-[#e5e5dc] p-4"
                        >
                            <p class="text-[14px] text-[#283618]">
                                <strong>{{ $sighting->contact_name }}</strong> zgłosił(a), że też widział(a) tego zwierzaka
                                {{ $sighting->date_seen?->locale('pl')->translatedFormat('d F Y') }}.
                            </p>

                            <p class="mt-2 whitespace-pre-line text-[14px] leading-relaxed text-[#616657]">{{ $sighting->description }}</p>

                            @if ($sighting->location)
                                <p class="mt-3 text-[12px] uppercase tracking-wide text-[#8f9485]">Lokalizacja</p>
                                <p class="mt-1 text-[14px] text-[#616657]">{{ $sighting->location }}</p>
                            @endif

                            @if ($sighting->latitude && $sighting->longitude)
                                <div
                                    id="sighting-map-{{ $sighting->id }}"
                                    class="isolate mt-2 h-48 w-full overflow-hidden rounded-xl"
                                ></div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', () => {
                                        window.initAnimalMap('sighting-map-{{ $sighting->id }}', {{ $sighting->latitude }}, {{ $sighting->longitude }}, { zoom: 13 });
                                    });
                                </script>
                            @endif

                            {{-- ** Miniatury stałej wielkości (nie skalują się z szerokością karty) —
                                 klik otwiera ten sam lightbox co w głównej galerii zdjęć ogłoszenia --}}
                            @if ($sighting->photos->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($sighting->photos as $index => $photo)
                                        <button
                                            type="button"
                                            @click="showLightbox({{ $index }})"
                                            class="h-24 w-24 cursor-zoom-in overflow-hidden rounded-xl"
                                        >
                                            <img
                                                src="{{ asset('storage/'.$photo->path) }}"
                                                alt="Zdjęcie ze zgłoszenia z {{ $sighting->date_seen?->format('d.m.Y') }}"
                                                class="h-full w-full object-cover transition hover:scale-105"
                                                loading="lazy"
                                            >
                                        </button>
                                    @endforeach
                                </div>

                                <div
                                    x-show="lightboxOpen"
                                    x-cloak
                                    @click.self="lightboxOpen = false"
                                    @keydown.escape.window="lightboxOpen = false"
                                    @keydown.arrow-right.window="nextPhoto()"
                                    @keydown.arrow-left.window="prevPhoto()"
                                    class="fixed inset-0 z-[1100] flex items-center justify-center bg-black/90 p-4"
                                >
                                    <button
                                        type="button"
                                        @click="lightboxOpen = false"
                                        class="absolute right-4 top-4 cursor-pointer text-[28px] text-white/80 hover:text-white"
                                        aria-label="Zamknij podgląd"
                                    >&times;</button>

                                    <button
                                        type="button"
                                        x-show="lightboxPhotos.length > 1"
                                        @click="prevPhoto()"
                                        class="absolute left-2 cursor-pointer p-2 text-[32px] text-white/80 hover:text-white sm:left-4"
                                        aria-label="Poprzednie zdjęcie"
                                    >&lsaquo;</button>

                                    <img
                                        :src="lightboxPhotos[lightboxIndex]"
                                        alt="Zdjęcie ze zgłoszenia"
                                        class="max-h-[85vh] max-w-full rounded-xl object-contain"
                                    >

                                    <button
                                        type="button"
                                        x-show="lightboxPhotos.length > 1"
                                        @click="nextPhoto()"
                                        class="absolute right-2 cursor-pointer p-2 text-[32px] text-white/80 hover:text-white sm:right-4"
                                        aria-label="Następne zdjęcie"
                                    >&rsaquo;</button>
                                </div>
                            @endif

                            <button
                                type="button"
                                @click="contactOpen = true"
                                class="mt-4 cursor-pointer rounded-xl border border-[#283618] px-4 py-2 text-[13px] font-semibold text-[#283618] transition hover:bg-[#283618] hover:text-[#fefae0] active:transform-[scale(0.97)] active:bg-[#1e2812]"
                            >
                                Kontakt z autorem
                            </button>

                            {{-- ** Modal z tym samym formularzem kontaktowym co karta Kontakt, ale do autora TEGO
                                 zgłoszenia — szerokość dopasowana do karty Kontakt, wyraźne tło i obramowanie,
                                 żeby nie zlewał się z resztą strony --}}
                            <div
                                x-show="contactOpen"
                                x-cloak
                                @click.self="contactOpen = false"
                                @keydown.escape.window="contactOpen = false"
                                class="fixed inset-0 z-[1100] flex items-center justify-center bg-black/50 p-4"
                            >
                                <div class="w-full max-w-sm max-h-[90vh] overflow-y-auto rounded-2xl border border-[#e5e5dc] bg-white p-5 shadow-2xl">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-[16px] font-semibold text-[#283618]">Kontakt z autorem zgłoszenia</h3>
                                        <button
                                            type="button"
                                            @click="contactOpen = false"
                                            class="cursor-pointer text-[20px] text-[#8f9485] hover:text-[#283618]"
                                            aria-label="Zamknij"
                                        >&times;</button>
                                    </div>
                                    <div class="mt-4">
                                        @include('animals.partials.contact-form', [
                                            'action' => route('sightings.messages.store', $sighting),
                                            'recipientLabel' => 'autorowi zgłoszenia',
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

@endsection
