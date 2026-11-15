@extends('layouts.public')

@section('title', 'Zmiana hasła - Noskiem.pl')

@section('content')

    {{-- ** Prośba o link do ustawienia nowego hasła (PasswordResetLinkController) --}}
    <div class="max-w-md mx-auto px-4 sm:px-6 py-16">
        <div class="rounded-2xl bg-white p-6 shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] sm:p-8">
            <h1 class="text-[24px] font-semibold text-[#283618]">Nie pamiętasz hasła?</h1>
            <p class="mt-2 text-[14px] leading-relaxed text-[#616657]">
                Podaj adres e-mail konta - wyślemy link, którym ustawisz nowe hasło.
            </p>

            @if (session('status'))
                <p class="mt-3 text-[13px] font-semibold text-[#3f6212]">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="text-[12px] uppercase tracking-wide text-[#8f9485]">Adres e-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden">
                    @error('email') <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]">
                    Wyślij link
                </button>
            </form>

            <p class="mt-5 text-center text-[13px] text-[#616657]">
                <a href="{{ route('login') }}" class="underline">Wróć do logowania</a>
            </p>
        </div>
    </div>

@endsection
