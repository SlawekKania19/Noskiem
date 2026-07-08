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

    $cardTitle = $animal->animal_name ?: $animal->title;
    $locationLabel = $animal->location_text ?: ($animal->city->name_pl ?? null);

    // ** Zdjęcia — główne na pierwszym miejscu
    $photos = $animal->photos->sortByDesc('is_main')->values();
@endphp

@extends('layouts.public')

@section('title', $cardTitle.' — Noskiem.pl')

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
             Galeria zdjęć (tabela photos)
             --------------------------- --}}
        <div class="mt-6">
            @if ($photos->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($photos as $photo)
                        <img
                            src="{{ asset('storage/'.$photo->path) }}"
                            alt="{{ $cardTitle }}"
                            class="h-40 w-full rounded-2xl object-cover sm:h-48"
                            loading="lazy"
                        >
                    @endforeach
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
                    class="mt-2 h-72 w-full overflow-hidden rounded-2xl"
                ></div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.initAnimalMap('animal-map', {{ $animal->latitude }}, {{ $animal->longitude }});
                });
            </script>
        @endif

        <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-3">

            {{-- ---------------------------
                 Opis, data zdarzenia, znaki szczególne
                 --------------------------- --}}
            <div class="lg:col-span-2">
                <h2 class="text-[16px] font-semibold text-[#283618]">Opis</h2>
                <p class="mt-2 whitespace-pre-line text-[14px] leading-relaxed text-[#616657]">{{ $animal->description }}</p>

                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Data zdarzenia</h2>
                <p class="mt-2 text-[14px] text-[#616657]">{{ $animal->date_event?->locale('pl')->translatedFormat('d F Y') }}</p>

                @if ($animal->ident_marks)
                    <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Znaki szczególne</h2>
                    <p class="mt-2 whitespace-pre-line text-[14px] leading-relaxed text-[#616657]">{{ $animal->ident_marks }}</p>
                @endif

                {{-- ** Zgłoszenie "widziałem" — formularz sightings dodamy w kolejnym kroku --}}
                <a
                    href="#"
                    class="mt-8 inline-flex items-center rounded-xl bg-[#283618] px-6 py-3 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition-colors hover:bg-[#1e2812]"
                >
                    Zgłoś, że widziałem
                </a>
            </div>

            {{-- ---------------------------
                 Kontakt do zgłaszającego
                 --------------------------- --}}
            <div x-data="{ showPhone: false }" class="rounded-2xl border border-[#e5e5dc] p-5">
                <h2 class="text-[16px] font-semibold text-[#283618]">Kontakt</h2>

                {{-- ** Telefon ukryty za przyciskiem (ochrona przed botami) --}}
                @if ($animal->contact_phone)
                    <div class="mt-4">
                        <p class="text-[12px] uppercase tracking-wide text-[#8f9485]">Telefon</p>
                        <button
                            type="button"
                            @click="showPhone = true"
                            x-show="!showPhone"
                            class="mt-1 rounded-xl border border-[#283618] px-4 py-2 text-[13px] font-semibold text-[#283618] hover:bg-[#283618] hover:text-[#fefae0] transition-colors"
                        >
                            Pokaż numer
                        </button>
                        <p x-show="showPhone" x-cloak class="mt-1 text-[15px] font-semibold text-[#283618]">
                            {{ $animal->contact_phone }}
                        </p>
                    </div>
                @endif

                {{-- ** Formularz "napisz wiadomość" — zapis do tabeli messages --}}
                <form method="POST" action="{{ route('messages.store', $animal) }}" class="mt-6 space-y-3">
                    @csrf

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Imię i nazwisko</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-none"
                        >
                        @error('name')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">E-mail</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-none"
                        >
                        @error('email')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Wiadomość</label>
                        <textarea
                            name="message"
                            rows="4"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-none"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition-colors hover:bg-[#1e2812]"
                    >
                        Wyślij wiadomość
                    </button>
                </form>
            </div>
        </div>
    </div>

@endsection
