@php
    // ** Rasy do filtrowania po stronie klienta (Alpine) w zależności od wybranego gatunku —
    // ta sama struktura co w formularzu zgłoszenia (resources/views/animals/create.blade.php)
    $breedsForJs = $breeds->map(fn ($b) => [
        'id' => $b->id,
        'species_id' => $b->species_id,
        'breed_pl' => $b->breed_pl,
    ])->values()->all();
@endphp

@extends('layouts.public')

@section('title', 'Mapa zaginionych / odnalezionych zwierzaków — Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Mapa wszystkich zatwierdzonych ogłoszeń — filtry działają po stronie przeglądarki
         (bez przeładowania strony), żeby zmiana filtra nie resetowała widoku mapy
         --------------------------- --}}
    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
        x-data='animalsMap({
            animals: @json($animals),
            breeds: @json($breedsForJs),
            initialStatus: @json($initialStatus),
        })'
    >
        <h1 class="text-[24px] font-semibold text-[#283618]">Mapa zaginionych / odnalezionych zwierzaków</h1>
        <p class="mt-1 text-[14px] text-[#616657]">
            Liczba ogłoszeń na mapie: <span x-text="filteredAnimals.length"></span>
        </p>

        {{-- ---------------------------
             Filtry — te same kryteria co na /animals, ale bez przycisku "Filtruj": każda
             zmiana od razu aktualizuje pinezki na mapie
             --------------------------- --}}
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">

            {{-- ** Gatunek --}}
            <select
                x-model="speciesId"
                @change="onSpeciesChange()"
                class="w-full min-w-0 truncate rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
            >
                <option value="">Gatunek</option>
                @foreach ($species as $s)
                    <option value="{{ $s->id }}">{{ $s->name_pl }}</option>
                @endforeach
            </select>

            {{-- ** Rasa --}}
            <select
                x-model="breedId"
                class="w-full min-w-0 truncate rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
            >
                <option value="">Rasa</option>
                <template x-for="breed in filteredBreeds" :key="breed.id">
                    <option :value="breed.id" x-text="breed.breed_pl"></option>
                </template>
            </select>

            {{-- ** Województwo + Miasto — komponent ma własny stan Alpine, więc synchronizujemy
                 go z filtrami mapy przez zdarzenie 'city-picker-change' (patrz city-picker.js) --}}
            <div @city-picker-change="onCityPickerChange($event.detail)" class="contents">
                <x-city-picker
                    :voivodeships="$voivodeships"
                    :required="false"
                    :show-labels="false"
                    voivodeship-placeholder="Województwo"
                    city-placeholder="Miejscowość (min. 3 litery)"
                    field-class="w-full min-w-0 truncate rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                />
            </div>

            {{-- ** Status --}}
            <select
                x-model="status"
                class="w-full min-w-0 truncate rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
            >
                <option value="">Status</option>
                <option value="lost">Zaginione</option>
                <option value="found">Znalezione</option>
            </select>

            {{-- ** Kolor dominujący --}}
            <select
                x-model="colorId"
                class="w-full min-w-0 truncate rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
            >
                <option value="">Kolor dominujący</option>
                @foreach ($colors as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>

            <div class="col-span-2 flex items-center sm:col-span-3 lg:col-span-6">
                <button
                    type="button"
                    x-show="hasActiveFilters"
                    x-cloak
                    @click="resetFilters()"
                    class="cursor-pointer text-[13px] font-semibold text-[#616657] transition-colors hover:text-[#283618] active:text-[#1e2812]"
                >
                    Wyczyść filtry
                </button>
            </div>
        </div>

        {{-- ---------------------------
             Mapa — legenda kolorów + kontener Leaflet z pinezkami
             --------------------------- --}}
        <div class="mt-4 flex items-center gap-4 text-[12px] text-[#616657]">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#994d0a]"></span> Zaginione</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#3f6212]"></span> Znalezione</span>
        </div>

        <div
            id="animals-map"
            class="isolate mt-2 h-[32rem] w-full overflow-hidden rounded-2xl border border-[#e5e5dc]"
            x-init="initMap('animals-map')"
            x-effect="renderMarkers()"
        ></div>
    </div>

@endsection
