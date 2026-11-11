@extends('layouts.public')

@section('title', 'Bezpłatna baza chipów - Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Bezpłatna baza chipów - wyszukiwarka po numerze + formularz rejestracji
         (patrz ChipRegistrationController)
         --------------------------- --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <h1 class="text-[24px] font-semibold text-[#283618]">Bezpłatna baza chipów</h1>
        <p class="mt-1 text-[14px] text-[#616657]">
            Znalazłeś zwierzaka z chipem? Sprawdź numer w naszej bazie. Masz zwierzaka z chipem?
            Zarejestruj go bezpłatnie - wpis pojawi się w bazie po zatwierdzeniu przez moderatora.
        </p>

        {{-- ---------------------------
             Wyszukiwarka po numerze chipa - wynik (max. jeden) dociągany AJAX-em
             z chips.search, telefon osobno z chips.phone po kliknięciu
             --------------------------- --}}
        <section
            x-data="{
                chip: '',
                loading: false,
                searched: false,
                error: false,
                result: null,
                showPhone: false,
                phone: null,
                phoneError: false,
                async search() {
                    const chip = this.chip.replace(/[\s\-]/g, '');
                    if (! chip) return;

                    this.loading = true;
                    this.error = false;
                    this.result = null;
                    this.showPhone = false;
                    this.phone = null;
                    this.phoneError = false;
                    try {
                        const response = await fetch('{{ route('chips.search') }}?chip=' + encodeURIComponent(chip), {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (! response.ok) { this.error = true; return; }
                        const data = await response.json();
                        this.result = data.found ? data.result : null;
                        this.searched = true;
                    } catch (e) {
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },
                async revealPhone() {
                    this.phoneError = false;
                    try {
                        const response = await fetch('{{ route('chips.phone') }}?chip=' + encodeURIComponent(this.result.chip_number));
                        if (! response.ok) { this.phoneError = true; return; }
                        this.phone = (await response.json()).phone;
                        this.showPhone = true;
                    } catch (e) {
                        this.phoneError = true;
                    }
                },
            }"
            class="mt-8 rounded-2xl border border-[#e5e5dc] bg-white p-5 shadow-[0px_2px_10px_0px_rgba(30,38,18,0.08)]"
        >
            <h2 class="text-[20px] font-semibold text-[#283618]">Wyszukaj chip</h2>

            <form @submit.prevent="search()" class="mt-3 flex flex-col gap-2 sm:flex-row">
                <label for="chip_search" class="sr-only">Numer chipa</label>
                <input
                    type="text"
                    id="chip_search"
                    x-model="chip"
                    @input="searched = false"
                    inputmode="numeric"
                    maxlength="25"
                    placeholder="Wpisz numer chipa, np. 616093900123456"
                    class="w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                >
                <button
                    type="submit"
                    :disabled="loading || ! chip.trim()"
                    class="cursor-pointer rounded-xl bg-[#283618] px-6 py-2 text-[14px] font-semibold text-[#fefae0] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span x-show="! loading">Szukaj</span>
                    <span x-show="loading" x-cloak>Szukam…</span>
                </button>
            </form>

            <p x-show="error" x-cloak class="mt-3 text-[13px] text-[#994d0a]">
                Nie udało się wyszukać. Spróbuj ponownie za chwilę.
            </p>

            <p x-show="searched && ! result && ! error" x-cloak class="mt-4 text-[14px] text-[#616657]">
                Nie znaleźliśmy tego numeru w naszej bazie. Sprawdź, czy numer jest poprawny - możesz też
                zapytać weterynarza o sprawdzenie w innych bazach.
            </p>

            {{-- ** Wynik - wszystkie wartości przez x-text (escapowane), bez x-html --}}
            <template x-if="result">
                <div class="mt-4 rounded-xl bg-[#f5f5f0] p-4">
                    <p class="text-[12px] uppercase tracking-wide text-[#8f9485]">Chip nr <span x-text="result.chip_number"></span></p>
                    <p class="mt-1 text-[18px] font-semibold text-[#283618]" x-text="result.animal_name"></p>

                    <dl class="mt-3 grid grid-cols-1 gap-3 text-[14px] sm:grid-cols-3">
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Gatunek</dt>
                            <dd class="text-[#283618]" x-text="result.species || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Rasa</dt>
                            <dd class="text-[#283618]" x-text="result.breed || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] uppercase tracking-wide text-[#8f9485]">Miejscowość</dt>
                            <dd class="text-[#283618]" x-text="[result.city, result.voivodeship].filter(Boolean).join(', ') || '-'"></dd>
                        </div>
                    </dl>

                    <div x-show="result.additional_info" class="mt-3">
                        <p class="text-[12px] uppercase tracking-wide text-[#8f9485]">Informacje dodatkowe</p>
                        <p class="whitespace-pre-line text-[14px] text-[#283618]" x-text="result.additional_info"></p>
                    </div>

                    <div class="mt-4">
                        <p class="text-[12px] uppercase tracking-wide text-[#8f9485]">Telefon do właściciela</p>
                        <button
                            type="button"
                            @click="revealPhone()"
                            x-show="! showPhone"
                            class="mt-1 cursor-pointer rounded-xl border border-[#283618] px-4 py-2 text-[13px] font-semibold text-[#283618] transition hover:bg-[#283618] hover:text-[#fefae0] active:transform-[scale(0.97)] active:bg-[#1e2812]"
                        >
                            Pokaż numer
                        </button>
                        <p x-show="showPhone" x-cloak x-text="phone" class="mt-1 text-[15px] font-semibold text-[#283618]"></p>
                        <p x-show="phoneError" x-cloak class="mt-1 text-[13px] text-red-600">Nie udało się pobrać numeru. Spróbuj ponownie za chwilę.</p>
                    </div>
                </div>
            </template>
        </section>

        {{-- ---------------------------
             Rejestracja chipa
             --------------------------- --}}
        <h2 class="mt-12 text-[20px] font-semibold text-[#283618]">Zarejestruj chip</h2>

        <form
            method="POST"
            action="{{ route('chips.store') }}"
            enctype="multipart/form-data"
            class="mt-4 space-y-8"
        >
            @csrf

            {{-- ** Honeypot - wypełnione = spam (patrz ChipRegistrationController::store) --}}
            <div style="position:absolute; left:-9999px;" aria-hidden="true">
                <label for="website">Strona www</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            @include('chips.partials.fields', ['stickerRequired' => true])

            {{-- ---------------------------
                 Akceptacja regulaminu i polityki prywatności
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
                Zarejestruj chip
            </button>
        </form>
    </div>

@endsection
