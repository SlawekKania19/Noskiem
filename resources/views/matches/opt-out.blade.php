@extends('layouts.public')

@section('title', 'Powiadomienia o podobnych ogłoszeniach - Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Wypisanie z maili "nowe ogłoszenie podobne do Twojego" (MatchOptOutController).
         Przycisk zamiast zapisu po samym wejściu - skanery poczty otwierają linki same.
         --------------------------- --}}
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        <h1 class="text-[28px] font-semibold text-[#283618]">Powiadomienia o podobnych ogłoszeniach</h1>

        @if ($done)
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Gotowe - nie będziemy już pisać o nowych podobnych ogłoszeniach dla ogłoszenia
                <strong class="text-[#283618]">{{ $animal->generated_title }}</strong>.
                Inne maile dotyczące ogłoszenia (np. wiadomości od osób, które je zobaczyły) dalej będą przychodzić.
            </p>

            <a href="{{ route('animals.index') }}" class="mt-8 inline-block rounded-xl border border-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#283618] transition hover:bg-[#f5f5f0]">
                Przeglądaj ogłoszenia
            </a>
        @else
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Gdy pojawi się nowe ogłoszenie podobne po zdjęciach do Twojego
                (<strong class="text-[#283618]">{{ $animal->generated_title }}</strong>), wysyłamy Ci maila -
                najwyżej raz na kilka dni. Możesz z tego zrezygnować dla tego ogłoszenia.
                Inne maile (np. wiadomości od osób, które je zobaczyły) dalej będą przychodzić.
            </p>

            {{-- ** Akcja na ten sam podpisany adres (podpis w parametrach URL) --}}
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-8">
                @csrf
                <button
                    type="submit"
                    class="cursor-pointer rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]"
                >
                    Nie wysyłaj mi więcej takich powiadomień
                </button>
            </form>
        @endif
    </div>

@endsection
