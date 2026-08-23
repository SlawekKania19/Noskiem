@extends('layouts.public')

@section('title', 'Zgłoś, że też widziałeś/aś — Noskiem.pl')

@push('head-assets')
    @vite(['resources/css/leaflet.css', 'resources/js/maps.js'])
@endpush

@section('content')

    {{-- ---------------------------
         Uproszczony formularz "też widziałem" pod ogłoszeniem — patrz SightingController
         --------------------------- --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <a href="{{ route('animals.show', $animal) }}" class="text-[13px] text-[#616657] hover:text-[#283618]">
            &larr; Wróć do ogłoszenia
        </a>

        <h1 class="mt-2 text-[24px] font-semibold text-[#283618]">Zgłoś, że też widziałeś/aś</h1>
        <p class="mt-1 text-[14px] text-[#616657]">
            Dotyczy ogłoszenia „{{ $animal->generated_title }}". Zgłoszenie trafi do moderacji
            i pojawi się pod ogłoszeniem po zatwierdzeniu.
        </p>

        <form method="POST" action="{{ route('sightings.store', $animal) }}" enctype="multipart/form-data" class="mt-8 space-y-8">
            @csrf

            {{-- ** Honeypot — pole niewidoczne dla ludzi, ale boty często wypełniają
                 każde pole formularza; wypełnione = spam (patrz SightingController::store) --}}
            <div style="position:absolute; left:-9999px;" aria-hidden="true">
                <label for="website">Strona www</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            {{-- ---------------------------
                 Opis i data
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Co widziałeś/aś?</h2>

                <div class="mt-3">
                    <label for="description" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Opis</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="date_seen" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Data zaobserwowania</label>
                    <input
                        type="date"
                        id="date_seen"
                        name="date_seen"
                        value="{{ old('date_seen') }}"
                        required
                        max="{{ now()->toDateString() }}"
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden sm:w-auto"
                    >
                    @error('date_seen')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- ---------------------------
                 Lokalizacja
                 --------------------------- --}}
            <section>
                <h2 class="text-[16px] font-semibold text-[#283618]">Lokalizacja</h2>
                <p class="mt-1 text-[13px] text-[#616657]">Kliknij na mapie, żeby ustawić pinezkę w miejscu, gdzie widziałeś/aś zwierzaka.</p>

                <div class="mt-4">
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
                                lat: @json(old('latitude') ? (float) old('latitude') : (float) $animal->latitude),
                                lng: @json(old('longitude') ? (float) old('longitude') : (float) $animal->longitude),
                                latInputId: 'latitude-input',
                                lngInputId: 'longitude-input',
                                locationTextInputId: 'location-text-input',
                                locateButtonId: 'locate-me-button',
                                locateErrorId: 'locate-me-error',
                            });
                        });
                    </script>
                </div>

                <div class="mt-4">
                    <label for="location-text-input" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Opis lokalizacji</label>
                    <input
                        type="text"
                        name="location"
                        id="location-text-input"
                        value="{{ old('location') }}"
                        placeholder="np. Bronowice, Kraków"
                        required
                        class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                    >
                    @error('location')
                        <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            {{-- ---------------------------
                 Zdjęcia — maks. 6
                 --------------------------- --}}
            <section
                x-data="{
                    photos: [],
                    maxPhotos: 6,
                    error: '',
                    addFiles(event) {
                        const newFiles = Array.from(event.target.files);
                        event.target.value = '';

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
                        this.error = '';
                        this.syncInput();
                    },
                    syncInput() {
                        const dt = new DataTransfer();
                        this.photos.forEach((photo) => dt.items.add(photo.file));
                        this.$refs.fileInput.files = dt.files;
                    },
                }"
            >
                <h2 class="text-[16px] font-semibold text-[#283618]">Zdjęcia (opcjonalnie)</h2>
                <p class="mt-1 text-[12px] text-[#8f9485]">Maksymalnie 6 zdjęć.</p>

                <input
                    type="file"
                    name="photos[]"
                    accept="image/*"
                    x-ref="fileInput"
                    @change="addFiles($event)"
                    class="hidden"
                >

                <p x-show="error" x-cloak class="mt-2 text-[12px] text-[#994d0a]" x-text="error"></p>

                @error('photos')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
                @error('photos.*')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror

                <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    <template x-for="(photo, i) in photos" :key="photo.url">
                        <div class="group relative">
                            <img
                                :src="photo.url"
                                :alt="photo.name"
                                class="h-20 w-full rounded-xl object-cover"
                            >
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
                        + Dodaj zdjęcie
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
                class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]"
            >
                Wyślij zgłoszenie
            </button>
        </form>
    </div>

@endsection
