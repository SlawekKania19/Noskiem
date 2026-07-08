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

@section('title', 'Noskiem.pl — Znajdziemy go noskiem')

@section('content')

    {{-- ---------------------------
         Hero Section 
         Stan "Szukam" jest domyślny, przełącznik zmienia treść bez przeładowania strony
         --------------------------- --}}
    <section x-data="{ mode: 'szukam' }" class="bg-[#FFFFFF]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
            {{-- ** Switcher Szukam / Znalazłem (Figma: node 22:229) --}}
            <div class="inline-flex items-center rounded-full bg-white p-1 shadow-[0px_2px_10px_0px_rgba(30,38,18,0.08)]">
                <button
                    type="button"
                    @click="mode = 'szukam'"
                    :class="mode === 'szukam' ? 'bg-[#283618] text-[#fefae0]' : 'text-[#616657]'"
                    class="rounded-full px-6 py-2 text-[14px] font-semibold transition-colors"
                >
                    Szukam
                </button>
                <button
                    type="button"
                    @click="mode = 'znalazlem'"
                    :class="mode === 'znalazlem' ? 'bg-[#283618] text-[#fefae0]' : 'text-[#616657]'"
                    class="rounded-full px-6 py-2 text-[14px] font-semibold transition-colors"
                >
                    Znalazłem
                </button>
            </div>
            {{-- ** Nagłówek i opis — treść zależna od wybranego trybu --}}
            <h1 class="mt-8 text-[32px] sm:text-[44px] font-semibold leading-tight text-[#283618]">
                <span x-show="mode === 'szukam'" x-cloak>{{ $heroHeadlineLost }}</span>
                <span x-show="mode === 'znalazlem'" x-cloak>{{ $heroHeadlineFound }}</span>
            </h1>

            <p class="mt-4 max-w-2xl mx-auto text-[15px] sm:text-[16px] text-[#616657]">
                <span x-show="mode === 'szukam'" x-cloak>{{ $heroDescriptionLost }}</span>
                <span x-show="mode === 'znalazlem'" x-cloak>{{ $heroDescriptionFound }}</span>
            </p>

            {{-- ** Wyszukiwarka --}}
            <form action="{{ route('animals.index') }}" method="GET" class="mt-8 mx-auto flex max-w-xl flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    name="q"
                    placeholder="Wpisz miasto, rasę lub imię zwierzaka..."
                    class="flex-1 rounded-xl border border-[#e5e5dc] px-5 py-3 text-[14px] text-[#283618] placeholder:text-[#a3a795] focus:border-[#283618] focus:outline-hidden"
                >
                <button
                    type="submit"
                    class="rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] hover:bg-[#1e2812] transition-colors"
                >
                    Szukaj
                </button>
            </form>

            {{-- ** Podpowiedź / CTA — inna treść w zależności od trybu --}}
            <p class="mt-4 text-[13px] text-[#616657]" x-show="mode === 'szukam'" x-cloak>
                Najpierw poszukaj, zanim dodasz ogłoszenie.
            </p>
            <a
                href="{{ route('animals.create') }}"
                class="mt-4 inline-flex text-[13px] font-semibold text-[#283618] underline underline-offset-2"
                x-show="mode === 'znalazlem'"
                x-cloak
            >
                + Dodaj ogłoszenie o znalezionym zwierzaku
            </a>
        </div>
        {{-- ---------------------------
             Sekcja "Poszukiwane" (status=lost) — widoczna w trybie Szukam
             --------------------------- --}}
        <div x-show="mode === 'znalazlem'" x-cloak class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="flex items-center justify-between">
                <h2 class="text-[20px] font-semibold text-[#283618]">Poszukiwane</h2>
                <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="text-[13px] font-semibold text-[#283618] hover:underline">
                    Zobacz wszystkie
                </a>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($lostAnimals as $animal)
                    <x-animal-card :animal="$animal" />
                @empty
                    <p class="col-span-full text-center text-[14px] text-[#8f9485]">
                        Brak zaginionych zwierząt w bazie.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- ---------------------------
             Sekcja "Widziane" (status=found) — widoczna w trybie Znalazłem
             --------------------------- --}}
        <div x-show="mode === 'szukam'" x-cloak class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="flex items-center justify-between">
                <h2 class="text-[20px] font-semibold text-[#283618]">Widziane</h2>
                <a href="{{ route('animals.index', ['status' => 'found']) }}" class="text-[13px] font-semibold text-[#283618] hover:underline">
                    Zobacz wszystkie
                </a>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($foundAnimals as $animal)
                    <x-animal-card :animal="$animal" />
                @empty
                    <p class="col-span-full text-center text-[14px] text-[#8f9485]">
                        Brak znalezionych zwierząt w bazie.
                    </p>
                @endforelse
            </div>
        </div>
    </section>

@endsection
