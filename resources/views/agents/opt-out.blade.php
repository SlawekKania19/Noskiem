@extends('layouts.public')

@section('title', 'Powiadomienia agenta - Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Wyłączenie maili z prośbą o pomoc (AgentAccountController::optOut).
         Przycisk zamiast zapisu po samym wejściu - skanery poczty otwierają linki same.
         Konto i obszary zostają; powiadomienia da się włączyć z powrotem w panelu agenta.
         --------------------------- --}}
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        <h1 class="text-[28px] font-semibold text-[#283618]">Powiadomienia agenta</h1>

        @if ($done)
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Gotowe - nie będziemy już wysyłać próśb o pomoc w poszukiwaniach.
                Twoje konto i obszary działania zostają; powiadomienia włączysz z powrotem
                w panelu agenta.
            </p>

            <a href="{{ route('agent.panel') }}" class="mt-8 inline-block rounded-xl border border-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#283618] transition hover:bg-[#f5f5f0]">
                Panel agenta
            </a>
        @else
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Jako Agent noskiem dostajesz maila, gdy w Twoim obszarze działania zaginie
                zwierzak. Możesz to wyłączyć - konto i obszary zostaną, a powiadomienia
                włączysz z powrotem w panelu agenta.
            </p>

            {{-- ** Akcja na ten sam podpisany adres (podpis w parametrach URL) --}}
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-8">
                @csrf
                <button
                    type="submit"
                    class="cursor-pointer rounded-xl bg-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]"
                >
                    Wyłącz powiadomienia
                </button>
            </form>
        @endif
    </div>

@endsection
