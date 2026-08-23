@extends('layouts.public')

@section('title', 'Kontakt — Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Strona kontaktowa — treść nad formularzem edytowalna z panelu Ustawień
         (App\Filament\Pages\Settings, pole "contact_page_intro", Markdown -> HTML)
         --------------------------- --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        <h1 class="text-[32px] font-semibold text-[#283618]">Kontakt</h1>

        @if ($introHtml)
            <div class="prose prose-neutral mt-6 max-w-none prose-headings:text-[#283618] prose-a:text-[#283618]">
                {!! $introHtml !!}
            </div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="mt-8 space-y-4">
            @csrf

            {{-- ** Honeypot — pole niewidoczne dla ludzi, ale boty często wypełniają
                 każde pole formularza; wypełnione = spam (patrz ContactController::store) --}}
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
            </div>

            <div>
                <label class="text-[12px] uppercase tracking-wide text-[#8f9485]">Wiadomość</label>
                <textarea
                    name="message"
                    rows="6"
                    required
                    class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                >{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="cursor-pointer rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]"
            >
                Wyślij wiadomość
            </button>
        </form>
    </div>

@endsection
