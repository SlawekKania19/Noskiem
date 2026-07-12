@php
    // Teksty pod przełącznikiem Zaginiony/Znaleziony, edytowalne z panelu Filament (App\Filament\Pages\Settings)
    $createFormHintLost = \App\Models\Setting::get('create_form_hint_lost', 'Opisz zwierzę jak najdokładniej — im więcej szczegółów, tym większa szansa na odnalezienie.');
    $createFormHintFound = \App\Models\Setting::get('create_form_hint_found', 'Dziękujemy za zgłoszenie — Twoja pomoc zwiększa szansę, że zwierzę wróci do domu.');

    // Wyliczone osobno (bez przecinków w wyrażeniu) — @json() w Blade dzieli argument
    // po przecinkach, więc zagnieżdżone wywołania w jednej linii psują escapowanie
    $initialStatus = old('status', request('status', ''));
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
            x-data='{ chipPresent: {{ old('chip_present') ? 'true' : 'false' }}, status: @json($initialStatus) }'
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
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz gatunek</option>
                            @foreach ($species as $s)
                                <option value="{{ $s->id }}" @selected((int) old('species_id') === $s->id)>{{ $s->name_pl }}</option>
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
                                <option value="{{ $b->id }}" @selected((int) old('breed_id') === $b->id)>{{ $b->breed_pl }}</option>
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

                <div class="mt-4">
                    <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Znaki szczególne</label>
                    <textarea
                        name="ident_marks"
                        rows="3"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('ident_marks') }}</textarea>
                    @error('ident_marks')
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

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Województwo</label>
                        <select
                            name="voivodeship_id"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz województwo</option>
                            @foreach ($voivodeships as $v)
                                <option value="{{ $v->id }}" @selected((int) old('voivodeship_id') === $v->id)>{{ $v->name_pl }}</option>
                            @endforeach
                        </select>
                        @error('voivodeship_id')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Miasto</label>
                        <select
                            name="city_id"
                            required
                            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                        >
                            <option value="">Wybierz miasto</option>
                            @foreach ($cities as $c)
                                <option value="{{ $c->id }}" @selected((int) old('city_id') === $c->id)>{{ $c->name_pl }}</option>
                            @endforeach
                        </select>
                        @error('city_id')
                            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                        @enderror
                    </div>
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
