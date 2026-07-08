@extends('layouts.public')

@section('title', 'Baza zwierząt — Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Lista ogłoszeń (Figma: desktop 33:130, mobile 33:2)
         --------------------------- --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <h1 class="text-[24px] font-semibold text-[#283618]">Baza zwierząt</h1>
        <p class="mt-1 text-[14px] text-[#616657]">Liczba ogłoszeń: {{ $animals->count() }}</p>

        {{-- ---------------------------
             Filtry ogłoszeń (Figma: desktop 33:130, mobile 33:2)
             Formularz GET — wybrane wartości zachowane przez @selected(request(...))
             --------------------------- --}}
        <form method="GET" action="{{ route('animals.index') }}" class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">

            {{-- ** Gatunek --}}
            <select name="species_id" class="rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-none">
                <option value="">Gatunek</option>
                @foreach ($species as $s)
                    <option value="{{ $s->id }}" @selected(request('species_id') == $s->id)>{{ $s->name_pl }}</option>
                @endforeach
            </select>

            {{-- ** Rasa --}}
            <select name="breed_id" class="rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-none">
                <option value="">Rasa</option>
                @foreach ($breeds as $b)
                    <option value="{{ $b->id }}" @selected(request('breed_id') == $b->id)>{{ $b->breed_pl }}</option>
                @endforeach
            </select>

            {{-- ** Województwo --}}
            <select name="voivodeship_id" class="rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-none">
                <option value="">Województwo</option>
                @foreach ($voivodeships as $v)
                    <option value="{{ $v->id }}" @selected(request('voivodeship_id') == $v->id)>{{ $v->name_pl }}</option>
                @endforeach
            </select>

            {{-- ** Miasto --}}
            <select name="city_id" class="rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-none">
                <option value="">Miasto</option>
                @foreach ($cities as $c)
                    <option value="{{ $c->id }}" @selected(request('city_id') == $c->id)>{{ $c->name_pl }}</option>
                @endforeach
            </select>

            {{-- ** Status --}}
            <select name="status" class="rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-none">
                <option value="">Status</option>
                <option value="lost" @selected(request('status') === 'lost')>Zaginione</option>
                <option value="found" @selected(request('status') === 'found')>Znalezione</option>
            </select>

            {{-- ** Kolor dominujący --}}
            <select name="color_id" class="rounded-xl border border-[#e5e5dc] px-3 py-2 text-[13px] text-[#283618] focus:border-[#283618] focus:outline-none">
                <option value="">Kolor dominujący</option>
                @foreach ($colors as $c)
                    <option value="{{ $c->id }}" @selected(request('color_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <div class="col-span-2 flex items-center gap-4 sm:col-span-3 lg:col-span-6">
                <button
                    type="submit"
                    class="rounded-xl bg-[#283618] px-6 py-2 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition-colors hover:bg-[#1e2812]"
                >
                    Filtruj
                </button>

                @if (request()->hasAny(['species_id', 'breed_id', 'voivodeship_id', 'city_id', 'status', 'color_id']))
                    <a href="{{ route('animals.index') }}" class="text-[13px] font-semibold text-[#616657] hover:text-[#283618]">
                        Wyczyść filtry
                    </a>
                @endif
            </div>
        </form>

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($animals as $animal)
                <x-animal-card :animal="$animal" />
            @empty
                <p class="col-span-full text-center text-[14px] text-[#8f9485]">
                    Brak ogłoszeń spełniających kryteria.
                </p>
            @endforelse
        </div>
    </div>

@endsection
