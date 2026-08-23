{{-- ---------------------------
     Formularz "napisz wiadomość" — współdzielony między kartą Kontakt (do autora
     ogłoszenia) a modalem "Kontakt z autorem" w timeline (do autora zgłoszenia
     "też widziałem"). Parametry: $action (URL), $recipientLabel (np. "autorowi
     ogłoszenia" / "autorowi zgłoszenia") — patrz MessageController.
     --------------------------- --}}
@php($recipientLabel ??= 'autorowi ogłoszenia')

<form method="POST" action="{{ $action }}" class="space-y-3">
    @csrf

    {{-- ** Honeypot — pole niewidoczne dla ludzi, ale boty często wypełniają
         każde pole formularza; wypełnione = spam (patrz MessageController) --}}
    <div style="position:absolute; left:-9999px;" aria-hidden="true">
        <label for="website">Strona www</label>
        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
    </div>

    <div>
        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Imię i nazwisko</label>
        <input
            type="text"
            name="name"
            value="{{ old('name') }}"
            required
            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
        >
        @error('name')
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">E-mail</label>
        <input
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
        >
        @error('email')
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-[12px] text-[#8f9485]">Twój adres email zostanie udostępniony wyłącznie {{ $recipientLabel }}.</p>
    </div>

    <div>
        <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Wiadomość</label>
        <textarea
            name="message"
            rows="4"
            required
            class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
        >{{ old('message') }}</textarea>
        @error('message')
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
    </div>

    {{-- ** Akceptacja regulaminu i polityki prywatności + zgoda na udostępnienie danych — wymagana przed wysłaniem --}}
    <div>
        <label class="flex items-start gap-2 text-[12px] text-[#616657]">
            <input
                type="checkbox"
                name="accept_terms"
                value="1"
                required
                class="mt-0.5 h-4 w-4 rounded-sm border-[#e5e5dc] text-[#283618] focus:ring-[#283618]"
            >
            <span>
                Akceptuję <a href="/regulamin" target="_blank" class="underline hover:text-[#283618]">regulamin</a>
                oraz <a href="/polityka-prywatnosci" target="_blank" class="underline hover:text-[#283618]">politykę prywatności</a>
                i zgadzam się na udostępnienie mojego imienia i nazwiska oraz adresu e-mail {{ $recipientLabel }} w celu kontaktu.
            </span>
        </label>
        @error('accept_terms')
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]"
    >
        Wyślij wiadomość
    </button>
</form>
