@extends('layouts.public')

@section('title', 'Agenci noskiem - pomóż szukać zaginionych zwierzaków')
@section('description', 'Zostań Agentem noskiem: wskaż na mapie okolice, w których możesz pomóc, a damy Ci znać, gdy zaginie tam zwierzak.')

@php
    $field = 'mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden';
    $label = 'text-[12px] uppercase tracking-wide text-[#8f9485]';
    $error = 'mt-1 text-[12px] text-[#994d0a]';
@endphp

@section('content')

    {{-- ---------------------------
         Strona "Agenci noskiem" + rejestracja (AgentAccountController).
         Konto wymaga tylko: imię, e-mail, hasło, miejscowość. Obszary działania
         agent dodaje już po zalogowaniu, w panelu (agents/panel.blade.php).
         --------------------------- --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">

            {{-- ** O co chodzi --}}
            <div>
                <p class="text-[12px] font-semibold uppercase tracking-wide text-[#994d0a]">Agenci noskiem</p>
                <h1 class="mt-2 text-[32px] font-semibold leading-tight text-[#283618]">Pomóż szukać zaginionych zwierzaków w swojej okolicy</h1>
                <p class="mt-4 text-[15px] leading-relaxed text-[#616657]">
                    Pierwsze godziny po zaginięciu są najważniejsze, a właściciel nie jest w stanie
                    sprawdzić wszystkiego sam. Agenci noskiem to osoby, które zgodziły się rozejrzeć
                    po swojej okolicy, gdy zaginie tam zwierzak.
                </p>

                <ol class="mt-8 space-y-5">
                    @foreach ([
                        ['Zakładasz konto', 'Wystarczy imię, adres e-mail i miejscowość.'],
                        ['Zaznaczasz na mapie swoje okolice', 'Dom, praca, trasa spacerów - ile chcesz. Dla każdego miejsca ustawiasz promień w kilometrach.'],
                        ['Dostajesz prośbę o pomoc', 'Gdy w Twoim obszarze pojawi się ogłoszenie o zaginionym zwierzaku, wyślemy Ci maila - najwyżej raz na godzinę.'],
                        ['Klikasz „Szukam”', 'Właściciel widzi na ogłoszeniu, ilu agentów już szuka jego zwierzaka.'],
                    ] as $i => [$title, $text])
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#283618] text-[13px] font-semibold text-[#fefae0]">{{ $i + 1 }}</span>
                            <div>
                                <p class="text-[15px] font-semibold text-[#283618]">{{ $title }}</p>
                                <p class="mt-0.5 text-[14px] leading-relaxed text-[#616657]">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-8 text-[13px] leading-relaxed text-[#8f9485]">
                    To nic nie kosztuje i do niczego nie zobowiązuje. Powiadomienia wyłączysz
                    jednym kliknięciem, a konto usuniesz w każdej chwili.
                </p>
            </div>

            {{-- ** Formularz rejestracji --}}
            <div class="lg:self-start rounded-2xl bg-white p-6 shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] sm:p-8">
                <h2 class="text-[20px] font-semibold text-[#283618]">Zostań agentem</h2>

                <form method="POST" action="{{ route('agents.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="{{ $label }}">Imię</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="60" autocomplete="given-name" class="{{ $field }}">
                        @error('name') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="{{ $label }}">Adres e-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="{{ $field }}">
                        @error('email') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-city-picker
                            :voivodeships="$voivodeships"
                            :selected-voivodeship-id="old('voivodeship_id')"
                            :selected-city-id="old('city_id')"
                            :selected-city-label="$selectedCityLabel"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="{{ $label }}">Hasło</label>
                            <input type="password" id="password" name="password" required autocomplete="new-password" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="password_confirmation" class="{{ $label }}">Powtórz hasło</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
                        </div>
                    </div>
                    @error('password') <p class="{{ $error }}">{{ $message }}</p> @enderror

                    <div>
                        <label class="flex items-start gap-2 text-[13px] leading-relaxed text-[#616657]">
                            <input type="checkbox" name="terms" value="1" required class="mt-0.5 h-4 w-4 rounded-sm border-[#e5e5dc] text-[#283618] focus:ring-[#283618]" @checked(old('terms'))>
                            <span>
                                Akceptuję <a href="/regulamin" target="_blank" class="font-semibold text-[#283618] underline">regulamin</a>
                                i <a href="/polityka-prywatnosci" target="_blank" class="font-semibold text-[#283618] underline">politykę prywatności</a>
                                oraz zgadzam się na maile z prośbą o pomoc w poszukiwaniach.
                            </span>
                        </label>
                        @error('terms') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#283618] px-6 py-3 text-[14px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c]">
                        Załóż konto agenta
                    </button>
                </form>

                <p class="mt-5 text-center text-[13px] text-[#616657]">
                    Masz już konto? <a href="{{ route('login') }}" class="font-semibold text-[#283618] underline">Zaloguj się</a>
                </p>
            </div>
        </div>
    </div>

@endsection
