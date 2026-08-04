@php
    // Teksty pod przełącznikiem Zaginiony/Znaleziony, edytowalne z panelu Filament (App\Filament\Pages\Settings)
    $createFormHintLost = \App\Models\Setting::get('create_form_hint_lost', 'Opisz zwierzę jak najdokładniej — im więcej szczegółów, tym większa szansa na odnalezienie.');
    $createFormHintFound = \App\Models\Setting::get('create_form_hint_found', 'Dziękujemy za zgłoszenie — Twoja pomoc zwiększa szansę, że zwierzę wróci do domu.');
    $locationHintLost = \App\Models\Setting::get('create_form_location_hint_lost', 'Wskaż miejsce, w którym zwierzę widziano po raz ostatni — to zwiększa szansę na odnalezienie.');
    $locationHintFound = \App\Models\Setting::get('create_form_location_hint_found', 'Wskaż dokładne miejsce, w którym znalazłeś zwierzę — pomoże to właścicielowi je zidentyfikować.');

    // Podpowiedzi znaków szczególnych — edytowalne w panelu (Ustawienia), jedna fraza w linii.
    // Wartość domyślna zgodna z App\Filament\Pages\Settings::mount()
    $identMarksTagsDefault = implode("\n", [
        'Blizna',
        'Kulawizna',
        'Zez',
        'Brak ucha',
        'Przycięty ogon',
        'Łaciata sierść',
        'Duża plama/znamię',
        'Różnokolorowe oczy (heterochromia)',
        'Obroża',
        'Sterylizowany/kastrowany',
    ]);
    $identMarksTags = collect(explode("\n", \App\Models\Setting::get('create_form_ident_marks_tags', $identMarksTagsDefault)))
        ->map(fn ($tag) => trim($tag))
        ->filter()
        ->values()
        ->all();

    // Wyliczone osobno (bez przecinków w wyrażeniu) — @json() w Blade dzieli argument
    // po przecinkach, więc zagnieżdżone wywołania w jednej linii psują escapowanie
    $initialStatus = old('status', request('status', ''));
    $initialSpeciesId = old('species_id', '');
    $initialBreedId = old('breed_id', '');

    // Rasy do filtrowania po stronie klienta (Alpine) w zależności od wybranego gatunku
    $breedsForJs = $breeds->map(fn ($b) => [
        'id' => $b->id,
        'species_id' => $b->species_id,
        'breed_pl' => $b->breed_pl,
    ])->values()->all();
@endphp

@extends('layouts.public')

@section('title', 'Dodaj ogłoszenie — Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Formularz zgłoszenia (Figma: desktop 37:101, mobile 37:2)
         Pola zgodne z walidacją w AnimalEditController@store
         --------------------------- --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <h1 class="text-[24px] font-semibold text-[#283618]">Dodaj ogłoszenie</h1>
        <p class="mt-1 text-[14px] text-[#616657]">
            Zgłoszenie trafi do moderacji i pojawi się w bazie po zatwierdzeniu.
        </p>

        <form
            method="POST"
            action="{{ route('animals.store') }}"
            enctype="multipart/form-data"
            x-data='{
                chipPresent: {{ old('chip_present') ? 'true' : 'false' }},
                status: @json($initialStatus),
                speciesId: @json($initialSpeciesId),
                breedId: @json($initialBreedId),
                breedsList: @json($breedsForJs),
            }'
            class="mt-8 space-y-8"
        >
            @csrf

            {{-- ---------------------------
                 Typ zgłoszenia
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Typ zgłoszenia</h2>
                <div class="mt-3 inline-flex items-center rounded-full border border-[#e5e5dc] bg-white p-1 shadow-[0px_2px_10px_0px_rgba(30,38,18,0.08)]">
                    <button
                        type="button"
                        @click="status = 'lost'"
                        :class="status === 'lost' ? 'bg-[#283618] text-[#fefae0]' : 'text-[#616657]'"
                        class="rounded-full px-6 py-2 text-[14px] font-semibold transition-colors"
                    >
                        Zaginiony
                    </button>
                    <button
                        type="button"
                        @click="status = 'found'"
                        :class="status === 'found' ? 'bg-[#283618] text-[#fefae0]' : 'text-[#616657]'"
                        class="rounded-full px-6 py-2 text-[14px] font-semibold transition-colors"
                    >
                        Znaleziony
                    </button>
                </div>
                <input type="hidden" name="status" :value="status">
                @error('status')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror

                <p class="mt-3 text-[13px] text-[#616657]" x-show="status === 'lost'" x-cloak>{{ $createFormHintLost }}</p>
                <p class="mt-3 text-[13px] text-[#616657]" x-show="status === 'found'" x-cloak>{{ $createFormHintFound }}</p>
            </section>

            {{-- ---------------------------
                 Dane zwierzęcia
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Dane zwierzęcia</h2>

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Imię zwierzaka</label>
                        <input
                            type="text"
                            name="animal_name"
                            value="{{ old('animal_name') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                        @error('animal_name')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Gatunek</label>
                        <select
                            name="species_id"
                            x-model="speciesId"
                            @change="breedId = ''"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz gatunek</option>
                            @foreach ($species as $s)
                                <option value="{{ $s->id }}">{{ $s->name_pl }}</option>
                            @endforeach
                        </select>
                        @error('species_id')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Rasa</label>
                        <select
                            name="breed_id"
                            x-model="breedId"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz rasę</option>
                            <template x-for="breed in breedsList.filter(b => !speciesId || String(b.species_id) === String(speciesId))" :key="breed.id">
                                <option :value="breed.id" x-text="breed.breed_pl"></option>
                            </template>
                        </select>
                        @error('breed_id')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-[14px] text-[#283618]">
                            <input type="hidden" name="chip_present" value="0">
                            <input
                                type="checkbox"
                                name="chip_present"
                                value="1"
                                x-model="chipPresent"
                                class="h-4 w-4 rounded-sm border-[#e5e5dc] text-[#283618] focus:ring-[#283618]"
                                @if (old('chip_present')) checked @endif
                            >
                            Zwierzę ma chip
                        </label>
                    </div>

                    <div x-show="chipPresent" x-cloak>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Numer chipa</label>
                        <input
                            type="text"
                            name="chip_number"
                            value="{{ old('chip_number') }}"
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                        @error('chip_number')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4" x-data="{
                    addTag(tag) {
                        const el = this.$refs.identMarks;
                        el.value = el.value.trim() ? el.value.trim() + ', ' + tag : tag;
                        el.dispatchEvent(new Event('input'));
                    },
                }">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Znaki szczególne</label>
                    <textarea
                        name="ident_marks"
                        rows="3"
                        x-ref="identMarks"
                        placeholder="Opisz znaki szczególne zwierzaka lub dodaj z listy poniżej"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('ident_marks') }}</textarea>
                    @error('ident_marks')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror

                    @if ($identMarksTags)
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($identMarksTags as $tag)
                                <button
                                    type="button"
                                    @click="addTag(@js($tag))"
                                    class="rounded-full border border-[#e5e5dc] bg-white px-3 py-1 text-[12px] text-[#616657] transition-colors hover:border-[#283618] hover:text-[#283618]"
                                >
                                    {{ $tag }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- ---------------------------
                 Ogłoszenie
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Ogłoszenie</h2>

                <div class="mt-3">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Tytuł ogłoszenia</label>
                    <input
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >
                    @error('title')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Opis</label>
                    <textarea
                        name="description"
                        rows="5"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Data zdarzenia</label>
                    <input
                        type="date"
                        name="date_event"
                        value="{{ old('date_event') }}"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >
                    @error('date_event')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- ---------------------------
                 Lokalizacja
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Lokalizacja</h2>
                <p class="mt-1 text-[13px] text-[#616657]" x-show="status === 'lost'" x-cloak>{{ $locationHintLost }}</p>
                <p class="mt-1 text-[13px] text-[#616657]" x-show="status === 'found'" x-cloak>{{ $locationHintFound }}</p>

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-city-picker
                        :voivodeships="$voivodeships"
                        :selected-voivodeship-id="old('voivodeship_id')"
                        :selected-city-id="old('city_id')"
                        :selected-city-label="$selectedCityName"
                    />
                </div>

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Opis lokalizacji</label>
                    <input
                        type="text"
                        name="location_text"
                        value="{{ old('location_text') }}"
                        placeholder="np. Bronowice, Kraków"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >
                    @error('location_text')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Wskaż lokalizację na mapie</label>
                    <p class="mt-1 text-[12px] text-[#8f9485]">Kliknij na mapie, żeby ustawić pinezkę w miejscu zdarzenia.</p>

                    <div
                        id="location-picker-map"
                        class="mt-2 h-72 w-full overflow-hidden rounded-2xl border border-[#e5e5dc]"
                    ></div>

                    <input type="hidden" name="latitude" id="latitude-input" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude-input" value="{{ old('longitude') }}">

                    @error('latitude')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                    @error('longitude')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            window.initLocationPicker('location-picker-map', {
                                lat: @json(old('latitude') ? (float) old('latitude') : null),
                                lng: @json(old('longitude') ? (float) old('longitude') : null),
                                latInputId: 'latitude-input',
                                lngInputId: 'longitude-input',
                            });
                        });
                    </script>
                </div>
            </section>

            {{-- ---------------------------
                 Zdjęcia — maks. 6, podgląd miniaturek przed wysłaniem
                 --------------------------- --}}
            <section
                x-data="{
                    photos: [],
                    maxPhotos: 6,
                    error: '',
                    handleFiles(event) {
                        const files = Array.from(event.target.files);

                        if (files.length > this.maxPhotos) {
                            this.error = 'Możesz dodać maksymalnie 6 zdjęć.';
                            this.photos = [];
                            event.target.value = '';
                            return;
                        }

                        this.error = '';
                        this.photos = files.map((file) => ({
                            name: file.name,
                            url: URL.createObjectURL(file),
                        }));
                    },
                }"
            >
                <h2 class="text-[16px] font-semibold text-[#283618]">Zdjęcia</h2>
                <p class="mt-1 text-[12px] text-[#8f9485]">Maksymalnie 6 zdjęć.</p>

                <input
                    type="file"
                    name="photos[]"
                    multiple
                    accept="image/*"
                    @change="handleFiles($event)"
                    class="mt-3 block w-full text-[14px] text-[#283618] file:mr-4 file:rounded-xl file:border-0 file:bg-[#283618] file:px-4 file:py-2 file:text-[13px] file:font-semibold file:text-[#fefae0] hover:file:bg-[#1e2812]"
                >

                <p x-show="error" x-cloak class="mt-2 text-[12px] text-[#994d0a]" x-text="error"></p>

                @error('photos')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
                @error('photos.*')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror

                <div x-show="photos.length > 0" x-cloak class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    <template x-for="photo in photos" :key="photo.url">
                        <img :src="photo.url" :alt="photo.name" class="h-20 w-full rounded-xl object-cover">
                    </template>
                </div>
            </section>

            {{-- ---------------------------
                 Dane kontaktowe
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Dane kontaktowe</h2>

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Imię i nazwisko</label>
                        <input
                            type="text"
                            name="contact_name"
                            value="{{ old('contact_name') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                        @error('contact_name')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">E-mail</label>
                        <input
                            type="email"
                            name="contact_email"
                            value="{{ old('contact_email') }}"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                        @error('contact_email')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Telefon (opcjonalnie)</label>
                        <input
                            type="text"
                            name="contact_phone"
                            value="{{ old('contact_phone') }}"
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                        @error('contact_phone')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <button
                type="submit"
                class="w-full rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition-colors hover:bg-[#1e2812] sm:w-auto"
            >
                Wyślij zgłoszenie
            </button>
        </form>
    </div>

@endsection
