@extends('layouts.public')

@section('title', 'Edytuj ogłoszenie — Noskiem.pl')

@push('head-assets')
    @vite(['resources/css/leaflet.css', 'resources/js/maps.js'])
@endpush

@section('content')

    {{-- ---------------------------
         Formularz edycji (analogiczny do animals/create.blade.php), wypełniony
         danymi istniejącego ogłoszenia. Token edycji przekazywany dalej do
         AnimalEditController@update zarówno w URL akcji, jak i w ukrytym polu.
         --------------------------- --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <h1 class="text-[24px] font-semibold text-[#283618]">Edytuj ogłoszenie</h1>
        <p class="mt-1 text-[14px] text-[#616657]">
            Zmiany trafią do moderacji i zostaną opublikowane po zatwierdzeniu. Do tego czasu widoczna jest poprzednia wersja ogłoszenia.
        </p>

        <form
            method="POST"
            action="{{ route('animals.update', ['animal' => $animal, 'token' => $animal->edit_token]) }}"
            enctype="multipart/form-data"
            x-data="{
                chipPresent: {{ old('chip_present', $animal->chip_present) ? 'true' : 'false' }},
                status: @json(old('status', $animal->status)),
                locationResolving: false,
            }"
            @location-resolving.window="locationResolving = true"
            @location-resolved-end.window="locationResolving = false"
            class="mt-8 space-y-8"
        >
            @csrf

            {{-- ** Token edycji — przekazywany też w URL powyżej, na wypadek gdyby middleware/proxy zgubiły query string --}}
            <input type="hidden" name="token" value="{{ $animal->edit_token }}">

            {{-- ---------------------------
                 Typ zgłoszenia
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Typ zgłoszenia</h2>
                <div class="mt-3">
                    <select
                        name="status"
                        x-model="status"
                        required
                        class="w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >
                        <option value="">Wybierz status</option>
                        <option value="lost" @selected(old('status', $animal->status) === 'lost')>Zaginiony</option>
                        <option value="found" @selected(old('status', $animal->status) === 'found')>Znaleziony</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>
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
                            value="{{ old('animal_name', $animal->animal_name) }}"
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
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Gatunek</label>
                        <select
                            name="species_id"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz gatunek</option>
                            @foreach ($species as $s)
                                <option value="{{ $s->id }}" @selected((int) old('species_id', $animal->species_id) === $s->id)>{{ $s->name_pl }}</option>
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
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz rasę</option>
                            @foreach ($breeds as $b)
                                <option value="{{ $b->id }}" @selected((int) old('breed_id', $animal->breed_id) === $b->id)>{{ $b->breed_pl }}</option>
                            @endforeach
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
                                @if (old('chip_present', $animal->chip_present)) checked @endif
                            >
                            Zwierzę ma chip
                        </label>
                    </div>

                    <div x-show="chipPresent" x-cloak>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Numer chipa</label>
                        <input
                            type="text"
                            name="chip_number"
                            value="{{ old('chip_number', $animal->chip_number) }}"
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
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Znaki szczególne</label>
                    <textarea
                        name="ident_marks"
                        rows="3"
                        x-ref="identMarks"
                        placeholder="Opisz znaki szczególne zwierzaka lub dodaj z listy poniżej"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('ident_marks', $animal->ident_marks) }}</textarea>
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
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Zachowanie (opcjonalnie)</label>
                    <textarea
                        name="behavior"
                        rows="2"
                        x-ref="behavior"
                        placeholder="Opisz zachowanie zwierzaka lub dodaj z listy poniżej"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('behavior', $animal->behavior) }}</textarea>
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
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Kolory (można wybrać kilka)</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($colors as $color)
                            <label class="cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="colors[]"
                                    value="{{ $color->id }}"
                                    class="peer hidden"
                                    @checked(in_array($color->id, old('colors', $animal->colors->pluck('id')->all())))
                                >
                                <span class="inline-flex rounded-full border border-[#e5e5dc] bg-white px-3 py-1 text-[12px] text-[#616657] transition peer-checked:border-[#283618] peer-checked:bg-[#283618] peer-checked:text-[#fefae0]">
                                    {{ $color->name }}
                                </span>
                            </label>
                        @endforeach
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
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Opis</label>
                    <textarea
                        name="description"
                        rows="5"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('description', $animal->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Data zdarzenia</label>
                    <input
                        type="date"
                        name="date_event"
                        value="{{ old('date_event', optional($animal->date_event)->format('Y-m-d')) }}"
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

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-city-picker
                        :voivodeships="$voivodeships"
                        :selected-voivodeship-id="old('voivodeship_id', $animal->voivodeship_id)"
                        :selected-city-id="old('city_id', $animal->city_id)"
                        :selected-city-label="$selectedCityName"
                    />
                </div>

                <div
                    class="mt-4"
                    x-data="{ resolving: false }"
                    @location-resolving.window="resolving = true"
                    @location-resolved-end.window="resolving = false"
                >
                    <label class="flex items-center gap-1.5 text-[12px] uppercase tracking-wide text-[#8f9485]">
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
                        value="{{ old('location_text', $animal->location_text) }}"
                        placeholder="np. Bronowice, Kraków"
                        required
                        :disabled="resolving"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden disabled:cursor-not-allowed disabled:bg-[#f5f5f0]"
                    >
                    @error('location_text')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Wskaż lokalizację na mapie</label>
                    <p class="mt-1 text-[12px] text-[#8f9485]">Kliknij na mapie, żeby zmienić pinezkę w miejscu zdarzenia.</p>

                    <button
                        type="button"
                        id="locate-me-button"
                        class="mt-2 inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-[#e5e5dc] bg-white px-3 py-1.5 text-[12px] font-medium text-[#283618] transition hover:border-[#283618] active:transform-[scale(0.97)] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        📍 Użyj mojej bieżącej lokalizacji
                    </button>
                    <p id="locate-me-error" class="mt-1 hidden text-[12px] text-[#994d0a]"></p>

                    <div
                        id="location-picker-map"
                        class="isolate mt-2 h-72 w-full overflow-hidden rounded-2xl border border-[#e5e5dc]"
                    ></div>

                    <input type="hidden" name="latitude" id="latitude-input" value="{{ old('latitude', $animal->latitude) }}">
                    <input type="hidden" name="longitude" id="longitude-input" value="{{ old('longitude', $animal->longitude) }}">

                    @error('latitude')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                    @error('longitude')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            window.initLocationPicker('location-picker-map', {
                                lat: @json(old('latitude', $animal->latitude)),
                                lng: @json(old('longitude', $animal->longitude)),
                                latInputId: 'latitude-input',
                                lngInputId: 'longitude-input',
                                locationTextInputId: 'location-text-input',
                                locateButtonId: 'locate-me-button',
                                locateErrorId: 'locate-me-error',
                            });
                        });
                    </script>
                </div>
            </section>

            {{-- ---------------------------
                 Zdjęcia — podgląd obecnych zdjęć ogłoszenia (tylko odczyt).
                 Zarządzanie zdjęciami przy edycji (dodawanie/usuwanie) to osobny krok —
                 AnimalEditController@update na razie nie przetwarza uploadu.
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Obecne zdjęcia</h2>

                @if ($animal->photos->isNotEmpty())
                    <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                        @foreach ($animal->photos as $photo)
                            <img
                                src="{{ asset('storage/'.$photo->path) }}"
                                alt="{{ $animal->animal_name }}"
                                class="h-20 w-full rounded-xl object-cover"
                            >
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-[13px] text-[#8f9485]">Brak zdjęć w tym ogłoszeniu.</p>
                @endif
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
                            value="{{ old('contact_name', $animal->contact_name) }}"
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
                            value="{{ old('contact_email', $animal->contact_email) }}"
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
                            value="{{ old('contact_phone', $animal->contact_phone) }}"
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
                :disabled="locationResolving"
                class="w-full rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition-colors hover:bg-[#1e2812] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            >
                Zapisz zmiany
            </button>
        </form>
    </div>

@endsection
