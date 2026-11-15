@extends('layouts.public')

@section('title', 'Nowe hasło - Noskiem.pl')

@php
    $field = 'mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden';
    $label = 'text-[12px] uppercase tracking-wide text-[#8f9485]';
    $error = 'mt-1 text-[12px] text-[#994d0a]';
@endphp

@section('content')

    {{-- ** Ustawienie nowego hasła z linku w mailu (NewPasswordController) --}}
    <div class="max-w-md mx-auto px-4 sm:px-6 py-16">
        <div class="rounded-2xl bg-white p-6 shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] sm:p-8">
            <h1 class="text-[24px] font-semibold text-[#283618]">Ustaw nowe hasło</h1>

            <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="{{ $label }}">Adres e-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username" class="{{ $field }}">
                    @error('email') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="{{ $label }}">Nowe hasło</label>
                    <input type="password" id="password" name="password" required autofocus autocomplete="new-password" class="{{ $field }}">
                    @error('password') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $label }}">Powtórz nowe hasło</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
                    @error('password_confirmation') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]">
                    Zapisz hasło
                </button>
            </form>
        </div>
    </div>

@endsection
