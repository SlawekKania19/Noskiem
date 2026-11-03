@php
    // ---------------------------
    // Pola formularza bazy chipów — wspólne dla rejestracji (chips.index) i edycji
    // przez właściciela (chips.edit). $values: aktualne dane wpisu (puste przy rejestracji),
    // $stickerRequired: naklejka wymagana przy rejestracji, opcjonalna przy edycji.
    // ---------------------------
    $values = $values ?? [];
    $value = fn (string $field) => old($field, $values[$field] ?? '');

    // Wyliczone osobno (bez przecinków w wyrażeniu) — @json() w Blade dzieli argument po przecinkach
    $initialSpeciesId = (string) $value('species_id');
    $initialBreedId = (string) $value('breed_id');
    $initialChipNumber = (string) $value('chip_number');
    $standardChipLength = \App\Models\ChipRegistration::STANDARD_CHIP_LENGTH;

    // Rasy do filtrowania po stronie klienta (Alpine) w zależności od wybranego gatunku
    $breedsForJs = $breeds->map(fn ($b) => [
        'id' => $b->id,
        'species_id' => $b->species_id,
        'breed_pl' => $b->breed_pl,
    ])->values()->all();

    $fieldClass = 'mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden';
    $labelClass = 'text-[12px] uppercase tracking-wide text-[#8f9485]';
@endphp

<div
    x-data='{
        chipNumber: @json($initialChipNumber),
        speciesId: @json($initialSpeciesId),
        breedId: @json($initialBreedId),
        breedsList: @json($breedsForJs),
        stickerUrl: null,
        // ** Same cyfry — spacje/myślniki z naklejki i tak usuwa kontroler
        get chipDigits() { return this.chipNumber.replace(/[\s\-]/g, ""); },
        getUnknownBreedId(speciesId) {
            const unknown = this.breedsList.find(
                (b) => String(b.species_id) === String(speciesId) && b.breed_pl === "Rasa nieznana"
            );
            return unknown ? String(unknown.id) : "";
        },
        previewSticker(event) {
            if (this.stickerUrl) URL.revokeObjectURL(this.stickerUrl);
            const file = event.target.files[0];
            this.stickerUrl = file ? URL.createObjectURL(file) : null;
        },
    }'
    class="space-y-8"
>
    {{-- ---------------------------
         Dane zwierzęcia
         --------------------------- --}}
    <section>
        <h3 class="text-[16px] font-semibold text-[#283618]">Dane zwierzęcia</h3>

        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="chip_number" class="{{ $labelClass }}">Numer chipa</label>
                <input
                    type="text"
                    id="chip_number"
                    name="chip_number"
                    x-model="chipNumber"
                    required
                    inputmode="numeric"
                    maxlength="25"
                    class="{{ $fieldClass }}"
                >
                {{-- ** Podpowiedź na żywo — krótsze numery dopuszczamy (starsze chipy), tylko informujemy --}}
                <p
                    x-show="/^[0-9]+$/.test(chipDigits) && chipDigits.length < {{ $standardChipLength }}"
                    x-cloak
                    class="mt-1 text-[12px] text-[#8f9485]"
                >
                    Zwykle chip ma {{ $standardChipLength }} cyfr — wpisano <span x-text="chipDigits.length"></span>. Sprawdź numer, chyba że to starszy chip.
                </p>
                <p
                    x-show="chipDigits.length && !/^[0-9]+$/.test(chipDigits)"
                    x-cloak
                    class="mt-1 text-[12px] text-[#994d0a]"
                >
                    Numer chipa może zawierać tylko cyfry.
                </p>
                @error('chip_number')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="animal_name" class="{{ $labelClass }}">Imię zwierzaka</label>
                <input
                    type="text"
                    id="animal_name"
                    name="animal_name"
                    value="{{ $value('animal_name') }}"
                    required
                    pattern="[\p{L}\s\-]+"
                    title="Tylko litery"
                    class="{{ $fieldClass }}"
                >
                @error('animal_name')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="species_id" class="{{ $labelClass }}">Gatunek</label>
                <select
                    id="species_id"
                    name="species_id"
                    x-model="speciesId"
                    @change="breedId = getUnknownBreedId(speciesId)"
                    required
                    class="{{ $fieldClass }}"
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
                <label for="breed_id" class="{{ $labelClass }}">Rasa</label>
                <select
                    id="breed_id"
                    name="breed_id"
                    x-model="breedId"
                    required
                    class="{{ $fieldClass }}"
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

            <div class="sm:col-span-2">
                <label for="additional_info" class="{{ $labelClass }}">Informacje dodatkowe (opcjonalnie)</label>
                <textarea
                    id="additional_info"
                    name="additional_info"
                    rows="3"
                    maxlength="2000"
                    placeholder="np. umaszczenie, znaki szczególne, choroby wymagające leków"
                    class="{{ $fieldClass }}"
                >{{ $value('additional_info') }}</textarea>
                @error('additional_info')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- ---------------------------
         Zdjęcie naklejki — widoczne tylko dla moderatora (dysk prywatny)
         --------------------------- --}}
    <section>
        <h3 class="text-[16px] font-semibold text-[#283618]">Zdjęcie naklejki z numerem chipa</h3>
        <p class="mt-1 text-[13px] text-[#616657]">
            @if ($stickerRequired)
                Naklejka z kodem kreskowym i numerem chipa (np. z książeczki zdrowia). Służy wyłącznie
                moderatorowi do weryfikacji i nie jest nigdzie publikowana.
            @else
                Zostaw puste, żeby zachować dotychczasowe zdjęcie. Dodaj nowe, jeśli zmieniasz numer chipa.
            @endif
        </p>

        <input
            type="file"
            id="sticker_photo"
            name="sticker_photo"
            accept="image/*"
            @if ($stickerRequired) required @endif
            @change="previewSticker($event)"
            class="mt-3 block w-full text-[13px] text-[#616657] file:mr-3 file:cursor-pointer file:rounded-full file:border file:border-[#e5e5dc] file:bg-white file:px-4 file:py-1.5 file:text-[13px] file:font-medium file:text-[#283618] hover:file:border-[#283618]"
        >
        <img x-show="stickerUrl" x-cloak :src="stickerUrl" alt="Podgląd naklejki" class="mt-3 max-h-48 rounded-xl border border-[#e5e5dc] object-contain">
        @error('sticker_photo')
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
    </section>

    {{-- ---------------------------
         Kontakt — telefon widoczny w wyszukiwarce (po kliknięciu), e-mail nigdy
         --------------------------- --}}
    <section>
        <h3 class="text-[16px] font-semibold text-[#283618]">Kontakt</h3>
        <p class="mt-1 text-[13px] text-[#616657]">
            Numer telefonu zobaczy osoba, która wyszuka chip Twojego zwierzaka. Adres e-mail
            służy tylko do potwierdzenia i edycji wpisu — nie jest udostępniany.
        </p>

        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="contact_phone" class="{{ $labelClass }}">Telefon</label>
                <input
                    type="tel"
                    id="contact_phone"
                    name="contact_phone"
                    value="{{ $value('contact_phone') }}"
                    required
                    maxlength="20"
                    class="{{ $fieldClass }}"
                >
                @error('contact_phone')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="contact_email" class="{{ $labelClass }}">E-mail</label>
                <input
                    type="email"
                    id="contact_email"
                    name="contact_email"
                    value="{{ $value('contact_email') }}"
                    required
                    class="{{ $fieldClass }}"
                >
                @error('contact_email')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>

            <x-city-picker
                :voivodeships="$voivodeships"
                :selected-voivodeship-id="$value('voivodeship_id')"
                :selected-city-id="$value('city_id')"
                :selected-city-label="$selectedCityName"
            />
        </div>
    </section>
</div>
