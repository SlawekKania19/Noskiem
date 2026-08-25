@php
    // Teksty pod przełącznikiem Zaginiony/Znaleziony, edytowalne z panelu Filament (App\Filament\Pages\Settings)
    $createFormHintLost = \App\Models\Setting::get('create_form_hint_lost', 'Opisz zwierzę jak najdokładniej — im więcej szczegółów, tym większa szansa na odnalezienie.');
    $createFormHintFound = \App\Models\Setting::get('create_form_hint_found', 'Dziękujemy za zgłoszenie — Twoja pomoc zwiększa szansę, że zwierzę wróci do domu.');
    $locationHintLost = \App\Models\Setting::get('create_form_location_hint_lost', 'Wskaż miejsce, w którym zwierzę widziano po raz ostatni — to zwiększa szansę na odnalezienie.');
    $locationHintFound = \App\Models\Setting::get('create_form_location_hint_found', 'Wskaż dokładne miejsce, w którym znalazłeś zwierzę — pomoże to właścicielowi je zidentyfikować.');

    // Wyliczone osobno (bez przecinków w wyrażeniu) — @json() w Blade dzieli argument
    // po przecinkach, więc zagnieżdżone wywołania w jednej linii psują escapowanie
    $initialStatus = old('status', request('status', ''));
    $initialSpeciesId = old('species_id', '');
    $initialBreedId = old('breed_id', '');
    $initialColorIds = collect(old('colors', []))->map(fn ($id) => (string) $id)->values()->all();

    // Rasy do filtrowania po stronie klienta (Alpine) w zależności od wybranego gatunku
    $breedsForJs = $breeds->map(fn ($b) => [
        'id' => $b->id,
        'species_id' => $b->species_id,
        'breed_pl' => $b->breed_pl,
    ])->values()->all();
@endphp

@extends('layouts.public')

@section('title', 'Dodaj ogłoszenie — Noskiem.pl')

@push('head-assets')
    @vite(['resources/css/leaflet.css', 'resources/js/maps.js'])
@endpush

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
            id="animal-create-form"
            method="POST"
            action="{{ route('animals.store') }}"
            enctype="multipart/form-data"
            x-data='{
                chipPresent: {{ old('chip_present') ? 'true' : 'false' }},
                status: @json($initialStatus),
                speciesId: @json($initialSpeciesId),
                breedId: @json($initialBreedId),
                breedsList: @json($breedsForJs),
                colorIds: @json($initialColorIds),
                locationResolving: false,
                getUnknownBreedId(speciesId) {
                    const unknown = this.breedsList.find(
                        (b) => String(b.species_id) === String(speciesId) && b.breed_pl === "Rasa nieznana"
                    );
                    return unknown ? String(unknown.id) : "";
                },
            }'
            @location-resolving.window="locationResolving = true"
            @location-resolved-end.window="locationResolving = false"
            class="mt-8 space-y-8"
        >
            @csrf

            {{-- ** Honeypot — pole niewidoczne dla ludzi, ale boty często wypełniają
                 każde pole formularza; wypełnione = spam (patrz AnimalEditController::store) --}}
            <div style="position:absolute; left:-9999px;" aria-hidden="true">
                <label for="website">Strona www</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            {{-- ---------------------------
                 Typ zgłoszenia
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Typ zgłoszenia</h2>
                <div class="mt-3 inline-flex items-center rounded-full border border-[#e5e5dc] bg-white p-1 shadow-[0px_2px_10px_0px_rgba(30,38,18,0.08)]">
                    <button
                        type="button"
                        @click="status = 'lost'"
                        :class="status === 'lost' ? 'bg-[#283618] text-[#fefae0] hover:bg-[#1e2812]' : 'text-[#616657] hover:bg-[#f4f4ef] hover:text-[#283618]'"
                        class="cursor-pointer rounded-full px-6 py-2 text-[14px] font-semibold transition active:transform-[scale(0.97)]"
                    >
                        Zaginiony
                    </button>
                    <button
                        type="button"
                        @click="status = 'found'"
                        :class="status === 'found' ? 'bg-[#283618] text-[#fefae0] hover:bg-[#1e2812]' : 'text-[#616657] hover:bg-[#f4f4ef] hover:text-[#283618]'"
                        class="cursor-pointer rounded-full px-6 py-2 text-[14px] font-semibold transition active:transform-[scale(0.97)]"
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
                        <label for="animal_name" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Imię zwierzaka</label>
                        <input
                            type="text"
                            id="animal_name"
                            name="animal_name"
                            value="{{ old('animal_name') }}"
                            :required="status === 'lost'"
                            pattern="[\p{L}\s\-]+"
                            title="Tylko litery"
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                        {{-- ** Przy "Znaleziony" imię prawie nigdy nie jest znane --}}
                        <p class="mt-1 text-[12px] text-[#8f9485]" x-show="status === 'found'" x-cloak>
                            Podaj tylko jeśli masz pewność - np. jeśli zwierzak ma zawieszkę lub obrożę z imieniem
                        </p>
                        @error('animal_name')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="species_id" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Gatunek</label>
                        <select
                            id="species_id"
                            name="species_id"
                            x-model="speciesId"
                            @change="breedId = getUnknownBreedId(speciesId)"
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
                        <label for="breed_id" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Rasa</label>
                        <select
                            id="breed_id"
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
                        <label for="chip_number" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Numer chipa</label>
                        <input
                            type="text"
                            id="chip_number"
                            name="chip_number"
                            value="{{ old('chip_number') }}"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            title="Tylko cyfry"
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
                    <label for="ident_marks" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Znaki szczególne</label>
                    <textarea
                        id="ident_marks"
                        name="ident_marks"
                        rows="3"
                        x-ref="identMarks"
                        placeholder="Opisz znaki szczególne zwierzaka lub dodaj z listy poniżej"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('ident_marks') }}</textarea>
                    @error('ident_marks')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror

                    @if ($identMarksTags->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($identMarksTags as $tag)
                                <button
                                    type="button"
                                    @click="addTag(@js($tag->name))"
                                    class="cursor-pointer rounded-full border border-[#e5e5dc] bg-white px-3 py-1 text-[12px] text-[#616657] transition hover:border-[#283618] hover:text-[#283618] active:transform-[scale(0.96)] active:bg-[#f4f4ef]"
                                >
                                    {{ $tag->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-4" x-data="{
                    addTag(tag) {
                        const el = this.$refs.behavior;
                        el.value = el.value.trim() ? el.value.trim() + ', ' + tag : tag;
                        el.dispatchEvent(new Event('input'));
                    },
                }">
                    <label for="behavior" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Zachowanie (opcjonalnie)</label>
                    <textarea
                        id="behavior"
                        name="behavior"
                        rows="2"
                        x-ref="behavior"
                        placeholder="Opisz zachowanie zwierzaka lub dodaj z listy poniżej"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('behavior') }}</textarea>
                    @error('behavior')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror

                    @if ($behaviors->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($behaviors as $b)
                                <button
                                    type="button"
                                    @click="addTag(@js($b->name))"
                                    class="cursor-pointer rounded-full border border-[#e5e5dc] bg-white px-3 py-1 text-[12px] text-[#616657] transition hover:border-[#283618] hover:text-[#283618] active:transform-[scale(0.96)] active:bg-[#f4f4ef]"
                                >
                                    {{ $b->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <p class="text-[12px] uppercase tracking-wide text-[#8f9485]">Kolory (można wybrać kilka)</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($colors as $color)
                            <label class="cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="colors[]"
                                    value="{{ $color->id }}"
                                    x-model="colorIds"
                                    class="peer hidden"
                                >
                                <span class="inline-flex rounded-full border border-[#e5e5dc] bg-white px-3 py-1 text-[12px] text-[#616657] transition peer-checked:border-[#283618] peer-checked:bg-[#283618] peer-checked:text-[#fefae0]">
                                    {{ $color->name }}
                                </span>
                            </label>
                        @endforeach

                        {{-- Niewidoczny checkbox — wykorzystuje natywną walidację przeglądarki, żeby
                             pokazać dymek z komunikatem bez przeładowania strony (tak jak przy imieniu
                             zwierzaka); prawdziwe kolory idą osobno jako colors[] powyżej --}}
                        <input
                            type="checkbox"
                            class="sr-only"
                            tabindex="-1"
                            aria-hidden="true"
                            x-effect="$el.setCustomValidity(colorIds.length > 0 ? '' : 'Wybierz przynajmniej jeden kolor zwierzaka.')"
                        >
                    </div>
                    @error('colors')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- ---------------------------
                 Ogłoszenie
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Ogłoszenie</h2>

                <div class="mt-3">
                    <label for="description" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Opis</label>
                    <textarea
                        id="description"
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
                    <label for="date_event" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Data zdarzenia</label>
                    <input
                        type="date"
                        id="date_event"
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

                <div class="mt-4">
                    <p class="mt-1 text-[12px] text-[#8f9485]">Kliknij na mapie, żeby ustawić pinezkę w miejscu zdarzenia — poniższe pola uzupełnią się automatycznie, <strong>upewnij się, że są poprawne.</strong></p>

                    <button
                        type="button"
                        id="locate-me-button"
                        class="mt-2 inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-[#e5e5dc] bg-white px-3 py-1.5 text-[12px] font-medium text-[#283618] transition hover:border-[#283618] active:transform-[scale(0.97)] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        📍 Użyj mojej bieżącej lokalizacji
                    </button>
                    <p id="locate-me-error" class="mt-1 hidden text-[12px] text-[#994d0a]"></p>

                    {{-- ** isolate — tworzy osobny kontekst stackowania, żeby z-index kontrolek Leaflet (do 1000) nie "wyciekał" nad elementy fixed (baner ciasteczek, dolna nawigacja mobilna) --}}
                    <div
                        id="location-picker-map"
                        class="isolate mt-2 h-72 w-full overflow-hidden rounded-2xl border border-[#e5e5dc]"
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
                                locationTextInputId: 'location-text-input',
                                locateButtonId: 'locate-me-button',
                                locateErrorId: 'locate-me-error',
                            });
                        });
                    </script>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-city-picker
                        :voivodeships="$voivodeships"
                        :selected-voivodeship-id="old('voivodeship_id')"
                        :selected-city-id="old('city_id')"
                        :selected-city-label="$selectedCityName"
                    />
                </div>

                <div
                    class="mt-4"
                    x-data="{ resolving: false }"
                    @location-resolving.window="resolving = true"
                    @location-resolved-end.window="resolving = false"
                >
                    <label for="location-text-input" class="flex items-center gap-1.5 text-[12px] uppercase tracking-wide text-[#8f9485]">
                        Opis lokalizacji
                        {{-- ** Kręcący się spinner na czas odczytu miasta/województwa z kliknięcia na mapie --}}
                        <span x-show="resolving" x-cloak class="inline-flex items-center gap-1 normal-case tracking-normal text-[#8f9485]">
                            <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Ustalam dokładną lokalizację pinezki...
                        </span>
                    </label>
                    <input
                        type="text"
                        name="location_text"
                        id="location-text-input"
                        value="{{ old('location_text') }}"
                        placeholder="np. Bronowice, Kraków"
                        required
                        :disabled="resolving"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden disabled:cursor-not-allowed disabled:bg-[#f5f5f0]"
                    >
                    @error('location_text')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
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
                    mainIndex: 0,
                    // ** Bez atrybutu multiple — z pojedynczym wyborem telefony pokazują
                    // pełny wybór 'Aparat / Galeria', z multiple niektóre przeglądarki (Safari
                    // na iOS) chowają opcję aparatu
                    addFiles(event) {
                        const newFiles = Array.from(event.target.files);
                        event.target.value = ''; // ** reset, żeby wybranie tego samego pliku ponownie też odpaliło change

                        if (this.photos.length + newFiles.length > this.maxPhotos) {
                            this.error = `Możesz dodać maksymalnie ${this.maxPhotos} zdjęć (masz już ${this.photos.length}).`;
                            return;
                        }

                        this.error = '';
                        newFiles.forEach((file) => {
                            this.photos.push({ file, name: file.name, url: URL.createObjectURL(file) });
                        });
                        this.syncInput();
                    },
                    removePhoto(i) {
                        URL.revokeObjectURL(this.photos[i].url);
                        this.photos.splice(i, 1);

                        if (i === this.mainIndex) {
                            this.mainIndex = 0;
                        } else if (i < this.mainIndex) {
                            this.mainIndex--;
                        }

                        this.error = '';
                        this.syncInput();
                    },
                    // ** Realny <input type=file> musi mieć skompletowaną listę plików do wysłania
                    // formularza — odtwarzamy ją z naszej tablicy przez DataTransfer
                    syncInput() {
                        const dt = new DataTransfer();
                        this.photos.forEach((photo) => dt.items.add(photo.file));
                        this.$refs.fileInput.files = dt.files;
                    },
                }"
            >
                <h2 class="text-[16px] font-semibold text-[#283618]">Zdjęcia</h2>
                <p class="mt-1 text-[12px] text-[#8f9485]">Maksymalnie 6 zdjęć — dodawaj po kolei, klikając „Dodaj zdjęcie".</p>

                <input
                    type="file"
                    name="photos[]"
                    accept="image/*"
                    x-ref="fileInput"
                    @change="addFiles($event)"
                    class="hidden"
                >

                <input type="hidden" name="main_photo_index" :value="mainIndex">

                <p x-show="error" x-cloak class="mt-2 text-[12px] text-[#994d0a]" x-text="error"></p>

                @error('photos')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
                @error('photos.*')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror

                <p x-show="photos.length > 1" x-cloak class="mt-3 text-[12px] text-[#8f9485]">
                    Kliknij gwiazdkę, żeby wybrać zdjęcie główne — to ono pojawi się na karcie ogłoszenia.
                </p>

                <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    <template x-for="(photo, i) in photos" :key="photo.url">
                        <div class="group relative">
                            <img
                                :src="photo.url"
                                :alt="photo.name"
                                class="h-20 w-full rounded-xl object-cover"
                                :class="mainIndex === i ? 'ring-2 ring-[#283618] ring-offset-1' : ''"
                            >
                            <button
                                type="button"
                                @click="mainIndex = i"
                                :title="mainIndex === i ? 'Zdjęcie główne' : 'Ustaw jako główne'"
                                class="absolute right-1 top-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full text-[13px] transition"
                                :class="mainIndex === i
                                    ? 'bg-[#283618] text-[#fefae0]'
                                    : 'bg-white/90 text-[#8f9485] opacity-0 group-hover:opacity-100'"
                            >★</button>
                            <button
                                type="button"
                                @click="removePhoto(i)"
                                title="Usuń zdjęcie"
                                class="absolute left-1 top-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-white/90 text-[13px] text-[#994d0a] opacity-0 transition group-hover:opacity-100"
                            >✕</button>
                        </div>
                    </template>

                    <button
                        type="button"
                        x-show="photos.length < maxPhotos"
                        @click="$refs.fileInput.click()"
                        class="flex h-20 w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-[#e5e5dc] text-[#8f9485] transition hover:border-[#283618] hover:text-[#283618]"
                    >
                        <span class="text-[22px] leading-none">+</span>
                    </button>
                </div>
            </section>

            {{-- ---------------------------
                 Dane kontaktowe
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Dane kontaktowe</h2>
                <p class="mt-1 text-[13px] text-[#616657]">
                    Twoje imię i nazwisko oraz adres email nie zostaną udostępnione innym użytkownikom.
                    Pokażemy tylko numer telefonu jeśli go podasz. Jeśli nie podasz numeru telefonu,
                    jedyną formą kontaktu z Tobą będzie formularz kontaktowy.
                </p>

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_name" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Imię i nazwisko</label>
                        <input
                            type="text"
                            id="contact_name"
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
                        <label for="contact_email" class="text-[12px] uppercase tracking-wide text-[#8f9485]">E-mail</label>
                        <input
                            type="email"
                            id="contact_email"
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
                        <label for="contact_phone" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Telefon (opcjonalnie)</label>
                        <input
                            type="text"
                            id="contact_phone"
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

            {{-- ---------------------------
                 Akceptacja regulaminu i polityki prywatności — wymagana przed wysłaniem
                 --------------------------- --}}
            <div>
                <label class="flex items-start gap-2 text-[13px] text-[#283618]">
                    <input
                        type="checkbox"
                        name="accept_terms"
                        value="1"
                        required
                        class="mt-0.5 h-4 w-4 rounded-sm border-[#e5e5dc] text-[#283618] focus:ring-[#283618]"
                        @if (old('accept_terms')) checked @endif
                    >
                    <span>
                        Akceptuję <a href="/regulamin" target="_blank" class="underline hover:text-[#616657]">regulamin</a>
                        oraz <a href="/polityka-prywatnosci" target="_blank" class="underline hover:text-[#616657]">politykę prywatności</a>.
                    </span>
                </label>
                @error('accept_terms')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                :disabled="locationResolving"
                class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            >
                Wyślij zgłoszenie
            </button>
        </form>
    </div>

@endsection
