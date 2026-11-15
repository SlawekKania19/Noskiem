@extends('layouts.public')

@section('title', 'Logowanie - Noskiem.pl')

@php
    $field = 'mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden';
    $label = 'text-[12px] uppercase tracking-wide text-[#8f9485]';
    $error = 'mt-1 text-[12px] text-[#994d0a]';
@endphp

@section('content')

    {{-- ---------------------------
         Logowanie - wspólne dla Agentów noskiem i kont panelu (po zalogowaniu
         /dashboard kieruje agenta do jego panelu, a resztę do /admin)
         --------------------------- --}}
    <div class="max-w-md mx-auto px-4 sm:px-6 py-16">
        <div class="rounded-2xl bg-white p-6 shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] sm:p-8">
            <h1 class="text-[24px] font-semibold text-[#283618]">Zaloguj się</h1>

            {{-- ** Np. "wysłaliśmy link do zmiany hasła" --}}
            @if (session('status'))
                <p class="mt-3 text-[13px] font-semibold text-[#3f6212]">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="{{ $label }}">Adres e-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="{{ $field }}">
                    @error('email') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="{{ $label }}">Hasło</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="{{ $field }}">
                    @error('password') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label for="remember_me" class="flex items-center gap-2 text-[13px] text-[#616657]">
                        <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded-sm border-[#e5e5dc] text-[#283618] focus:ring-[#283618]">
                        Zapamiętaj mnie
                    </label>
                    <a href="{{ route('password.request') }}" class="text-[13px] text-[#616657] underline">Nie pamiętam hasła</a>
                </div>

                <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]">
                    Zaloguj się
                </button>
            </form>

            <p class="mt-5 text-center text-[13px] text-[#616657]">
                Nie masz konta? <a href="{{ route('agents.register') }}" class="font-semibold text-[#283618] underline">Zostań Agentem noskiem</a>
            </p>
        </div>
    </div>

@endsection
