@extends('layouts.public')

@section('title', 'Edycja wpisu w bazie chipów — Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Edycja wpisu w bazie chipów przez właściciela (link z tokenem) —
         patrz ChipRegistrationController::edit/update/destroy
         --------------------------- --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <a href="{{ route('chips.index') }}" class="text-[13px] text-[#616657] hover:text-[#283618]">
            &larr; Baza chipów
        </a>

        <h1 class="mt-2 text-[24px] font-semibold text-[#283618]">Edycja wpisu — {{ $registration->animal_name }}</h1>
        <p class="mt-1 text-[14px] text-[#616657]">Chip nr {{ $registration->chip_number }}</p>

        {{-- ** Stan wpisu — co się stanie po zapisaniu zmian --}}
        <div class="mt-4 rounded-xl bg-[#f5f5f0] px-4 py-3 text-[13px] text-[#616657]">
            @if ($registration->mod_status === 'pending')
                Wpis czeka jeszcze na zatwierdzenie przez moderatora. Zmiany zostaną zapisane od razu.
            @elseif ($registration->pending_changes)
                Twoje poprzednie zmiany czekają na zatwierdzenie — formularz je pokazuje. Do tego czasu
                w wyszukiwarce widoczna jest wcześniejsza wersja wpisu.
            @else
                Wpis jest widoczny w wyszukiwarce. Po zapisaniu zmiany trafią do moderacji — do czasu
                zatwierdzenia widoczna będzie obecna wersja.
            @endif
        </div>

        <form
            method="POST"
            action="{{ route('chips.update', $registration) }}"
            enctype="multipart/form-data"
            class="mt-8 space-y-8"
        >
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            @include('chips.partials.fields', ['stickerRequired' => false])

            <button
                type="submit"
                class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]"
            >
                Zapisz zmiany
            </button>
        </form>

        {{-- ---------------------------
             Usunięcie wpisu — od razu, bez moderacji
             --------------------------- --}}
        <section class="mt-12 border-t border-[#e5e5dc] pt-8">
            <h2 class="text-[16px] font-semibold text-[#283618]">Usuń wpis</h2>
            <p class="mt-1 text-[13px] text-[#616657]">
                Wpis zniknie z bazy chipów od razu, razem ze zdjęciem naklejki. Tej operacji nie można cofnąć.
            </p>

            <form
                method="POST"
                action="{{ route('chips.destroy', $registration) }}"
                onsubmit="return confirm('Na pewno usunąć wpis z bazy chipów?');"
                class="mt-3"
            >
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button
                    type="submit"
                    class="cursor-pointer rounded-xl border border-[#994d0a] px-4 py-2 text-[13px] font-semibold text-[#994d0a] transition hover:bg-[#994d0a] hover:text-white active:transform-[scale(0.97)]"
                >
                    Usuń wpis
                </button>
            </form>
        </section>
    </div>

@endsection
