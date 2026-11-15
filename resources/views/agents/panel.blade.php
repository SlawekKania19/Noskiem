@extends('layouts.public')

@section('title', 'Panel agenta - Noskiem.pl')

@push('head-assets')
    @vite(['resources/css/leaflet.css', 'resources/js/maps.js'])
@endpush

@php
    $field = 'mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden';
    $label = 'text-[12px] uppercase tracking-wide text-[#8f9485]';
    $error = 'mt-1 text-[12px] text-[#994d0a]';
    $card = 'rounded-2xl bg-white p-6 shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)]';
    $primary = 'cursor-pointer rounded-xl bg-[#283618] px-5 py-2.5 text-[13px] font-semibold text-[#fefae0] shadow-[0px_3px_10px_0px_rgba(40,54,24,0.2)] transition hover:bg-[#1e2812] active:transform-[scale(0.98)] active:bg-[#161f0c] disabled:cursor-not-allowed disabled:opacity-50';
    $secondary = 'cursor-pointer rounded-xl border border-[#283618] px-4 py-2 text-[13px] font-semibold text-[#283618] transition hover:bg-[#f5f5f0] disabled:cursor-not-allowed disabled:opacity-50';

    $minRadius = config('noskiem_agents.min_radius_km');
    $maxRadius = config('noskiem_agents.max_radius_km');
    $canAddArea = $areas->count() < config('noskiem_agents.max_areas');
@endphp

@section('content')

    {{-- ---------------------------
         Panel Agenta noskiem (AgentPanelController): obszary działania na mapie,
         ogłoszenia z kliknięciem "Szukam", dane konta, hasło, usunięcie konta.
         --------------------------- --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-[12px] font-semibold uppercase tracking-wide text-[#994d0a]">Agent noskiem</p>
                <h1 class="mt-1 text-[28px] font-semibold text-[#283618]">Cześć, {{ explode(' ', trim($agent->name))[0] }}!</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $secondary }}">Wyloguj</button>
            </form>
        </div>

        {{-- ---------------------------
             Komunikaty o stanie konta
             --------------------------- --}}
        @if ($agent->agent_blocked_at)
            <div class="mt-6 rounded-2xl border border-[#f0c9b0] bg-[#fdf0e8] p-4 text-[14px] text-[#283618]">
                <p class="font-semibold">Konto agenta jest zablokowane.</p>
                <p class="mt-1 text-[#616657]">Nie wysyłamy powiadomień i nie możesz oznaczać ogłoszeń. Jeśli to pomyłka, <a href="{{ route('contact.show') }}" class="font-semibold underline">napisz do nas</a>.</p>
            </div>
        @elseif (! $agent->email_verified_at)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#f0c9b0] bg-[#fdf0e8] p-4 text-[14px] text-[#283618]">
                <div>
                    <p class="font-semibold">Potwierdź adres e-mail.</p>
                    <p class="mt-1 text-[#616657]">Wysłaliśmy link na {{ $agent->email }}. Do czasu potwierdzenia nie wysyłamy powiadomień i przycisk „Szukam” jest nieaktywny.</p>
                </div>
                <form method="POST" action="{{ route('agent.resend') }}">
                    @csrf
                    <button type="submit" class="{{ $secondary }}">Wyślij link ponownie</button>
                </form>
            </div>
        @elseif (! $agent->agent_notify)
            <div class="mt-6 rounded-2xl border border-[#e5e5dc] bg-[#f4f4ef] p-4 text-[14px] text-[#616657]">
                Powiadomienia są wyłączone - nie dostaniesz maila, gdy w Twoim obszarze zaginie zwierzak. Włączysz je niżej, w danych konta.
            </div>
        @elseif ($areas->isEmpty())
            <div class="mt-6 rounded-2xl border border-[#e5e5dc] bg-[#f4f4ef] p-4 text-[14px] text-[#616657]">
                Nie masz jeszcze żadnego obszaru działania. Dodaj pierwszy poniżej - bez niego nie wiemy, o których ogłoszeniach Ci pisać.
            </div>
        @endif

        {{-- ---------------------------
             Obszary działania - mapa + lista (resources/js/agent-areas.js)
             --------------------------- --}}
        <section
            class="mt-8"
            x-data="agentAreas({
                areas: @js($areas->map->only(['id', 'name', 'latitude', 'longitude', 'radius_km'])->values()),
                defaultRadius: @js((int) config('noskiem_agents.default_radius_km')),
                draft: @js(old('latitude') !== null ? ['lat' => (float) old('latitude'), 'lng' => (float) old('longitude'), 'radius' => (int) old('radius_km', config('noskiem_agents.default_radius_km'))] : null),
                searchUrl: @js(route('location.search')),
            })"
        >
            <h2 class="text-[20px] font-semibold text-[#283618]">Obszary działania</h2>
            <p class="mt-1 text-[14px] text-[#616657]">
                Gdy w którymś z nich pojawi się ogłoszenie o zaginionym zwierzaku, wyślemy Ci maila z prośbą o pomoc.
            </p>

            <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-5">

                {{-- ** Mapa: zielone okręgi = zapisane obszary, pomarańczowy = nowy --}}
                <div class="lg:col-span-3">
                    <div x-ref="map" class="h-[360px] w-full overflow-hidden rounded-2xl border border-[#e5e5dc] sm:h-[520px] lg:sticky lg:top-24"></div>
                </div>

                <div class="space-y-6 lg:col-span-2">

                    {{-- ** Nowy obszar --}}
                    <div class="{{ $card }}">
                        <h3 class="text-[16px] font-semibold text-[#283618]">Dodaj obszar</h3>

                        @if ($canAddArea)
                            <p class="mt-1 text-[13px] text-[#616657]">Kliknij w mapę albo wyszukaj adres, a potem ustaw promień.</p>

                            <div class="mt-4">
                                <label for="area-query" class="{{ $label }}">Adres lub miejscowość</label>
                                <div class="mt-1 flex gap-2">
                                    <input
                                        type="text"
                                        id="area-query"
                                        x-model="query"
                                        @keydown.enter.prevent="search()"
                                        placeholder="np. Katowice, Mariacka 10"
                                        class="w-full min-w-0 rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden"
                                    >
                                    <button type="button" @click="search()" :disabled="searching" class="{{ $secondary }} shrink-0">Szukaj</button>
                                </div>
                                <button type="button" @click="locate()" class="mt-2 cursor-pointer text-[13px] font-semibold text-[#283618] underline">Użyj mojej lokalizacji</button>
                                <p x-show="searchError" x-cloak x-text="searchError" class="{{ $error }}"></p>
                            </div>

                            <form method="POST" action="{{ route('agent.areas.store') }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="latitude" :value="draft.lat ?? ''">
                                <input type="hidden" name="longitude" :value="draft.lng ?? ''">

                                <div>
                                    <label for="area-radius" class="{{ $label }}">Promień: <span class="font-semibold text-[#283618]" x-text="draft.radius + ' km'"></span></label>
                                    <input
                                        type="range"
                                        id="area-radius"
                                        name="radius_km"
                                        min="{{ $minRadius }}"
                                        max="{{ $maxRadius }}"
                                        step="1"
                                        x-model.number="draft.radius"
                                        @input="onDraftRadius()"
                                        class="mt-2 w-full accent-[#283618]"
                                    >
                                    @error('radius_km') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="area-name" class="{{ $label }}">Nazwa (opcjonalnie)</label>
                                    <input type="text" id="area-name" name="name" value="{{ old('name') }}" maxlength="60" placeholder="np. Dom, Praca" class="{{ $field }}">
                                    @error('name') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>

                                @error('latitude') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                @error('longitude') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                @error('area') <p class="{{ $error }}">{{ $message }}</p> @enderror

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" :disabled="draft.lat === null" class="{{ $primary }}">Dodaj obszar</button>
                                    <button type="button" x-show="draft.lat !== null" x-cloak @click="clearDraft()" class="cursor-pointer text-[13px] text-[#616657] underline">Wyczyść punkt</button>
                                    <span x-show="draft.lat === null" class="text-[12px] text-[#8f9485]">Najpierw wskaż miejsce na mapie.</span>
                                </div>
                            </form>
                        @else
                            <p class="mt-2 text-[13px] text-[#616657]">Masz już maksymalną liczbę obszarów ({{ config('noskiem_agents.max_areas') }}). Usuń któryś, żeby dodać nowy.</p>
                        @endif
                    </div>

                    {{-- ** Zapisane obszary: suwak zmienia okrąg na żywo, zapis po "Zapisz" --}}
                    @if ($areas->isNotEmpty())
                        <div class="{{ $card }}">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-[16px] font-semibold text-[#283618]">Twoje obszary ({{ $areas->count() }})</h3>
                                <button type="button" @click="fitAll()" class="cursor-pointer text-[13px] font-semibold text-[#283618] underline">Pokaż wszystkie</button>
                            </div>

                            <ul class="mt-4 space-y-4">
                                @foreach ($areas as $i => $area)
                                    <li
                                        x-data="{ area: areas[{{ $i }}] }"
                                        :class="activeId === area.id ? 'border-[#283618]' : 'border-[#e5e5dc]'"
                                        class="rounded-xl border p-4"
                                    >
                                        <form method="POST" action="{{ route('agent.areas.update', $area) }}" class="space-y-3">
                                            @csrf
                                            @method('PATCH')

                                            <div class="flex items-center gap-2">
                                                <input type="text" name="name" value="{{ $area->name }}" maxlength="60" placeholder="Obszar {{ $i + 1 }}" aria-label="Nazwa obszaru" class="w-full min-w-0 rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] font-semibold text-[#283618] focus:border-[#283618] focus:outline-hidden">
                                                <button type="button" @click="focus(area.id)" class="shrink-0 cursor-pointer text-[13px] font-semibold text-[#283618] underline">Pokaż</button>
                                            </div>

                                            <div>
                                                <label class="{{ $label }}">Promień: <span class="font-semibold text-[#283618]" x-text="area.radius_km + ' km'"></span></label>
                                                <input
                                                    type="range"
                                                    name="radius_km"
                                                    min="{{ $minRadius }}"
                                                    max="{{ $maxRadius }}"
                                                    step="1"
                                                    x-model.number="area.radius_km"
                                                    @input="onAreaRadius(area)"
                                                    aria-label="Promień obszaru w kilometrach"
                                                    class="mt-2 w-full accent-[#283618]"
                                                >
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <button type="submit" class="{{ $secondary }}">Zapisz</button>
                                                <button type="submit" form="area-delete-{{ $area->id }}" class="cursor-pointer text-[13px] text-[#994d0a] underline">Usuń</button>
                                            </div>
                                        </form>

                                        <form id="area-delete-{{ $area->id }}" method="POST" action="{{ route('agent.areas.destroy', $area) }}" onsubmit="return confirm('Usunąć ten obszar?')">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ---------------------------
             Ogłoszenia, pod którymi agent kliknął "Szukam"
             --------------------------- --}}
        <section class="mt-12">
            <h2 class="text-[20px] font-semibold text-[#283618]">Szukam</h2>

            @if ($searched->isEmpty())
                <p class="mt-1 text-[14px] text-[#616657]">
                    Tu pojawią się ogłoszenia, przy których klikniesz „Szukam”. Przycisk znajdziesz na stronie każdego
                    <a href="{{ route('animals.index', ['status' => 'lost']) }}" class="font-semibold text-[#283618] underline">ogłoszenia o zaginionym zwierzaku</a>.
                </p>
            @else
                <p class="mt-1 text-[14px] text-[#616657]">Ogłoszenia, przy których kliknąłeś/aś „Szukam”.</p>

                <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($searched as $animal)
                        <x-animal-card :animal="$animal" />
                    @endforeach
                </div>
            @endif
        </section>

        <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- ---------------------------
                 Dane konta i powiadomienia
                 --------------------------- --}}
            <section class="{{ $card }}">
                <h2 class="text-[18px] font-semibold text-[#283618]">Dane konta</h2>
                <p class="mt-1 text-[13px] text-[#8f9485]">{{ $agent->email }}</p>

                <form method="POST" action="{{ route('agent.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="{{ $label }}">Imię</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $agent->name) }}" required maxlength="60" class="{{ $field }}">
                        @error('name') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-city-picker
                            :voivodeships="$voivodeships"
                            :selected-voivodeship-id="old('voivodeship_id', $agent->voivodeship_id)"
                            :selected-city-id="old('city_id', $agent->city_id)"
                            :selected-city-label="$agent->city?->label"
                        />
                    </div>

                    <label class="flex items-start gap-2 text-[14px] text-[#283618]">
                        <input type="hidden" name="agent_notify" value="0">
                        <input type="checkbox" name="agent_notify" value="1" class="mt-0.5 h-4 w-4 rounded-sm border-[#e5e5dc] text-[#283618] focus:ring-[#283618]" @checked(old('agent_notify', $agent->agent_notify))>
                        <span>Wysyłaj mi maile z prośbą o pomoc, gdy w moim obszarze zaginie zwierzak</span>
                    </label>

                    <button type="submit" class="{{ $primary }}">Zapisz</button>
                </form>
            </section>

            <div class="space-y-6">

                {{-- ---------------------------
                     Zmiana hasła (standardowy PasswordController, błędy w worku updatePassword)
                     --------------------------- --}}
                <section class="{{ $card }}">
                    <h2 class="text-[18px] font-semibold text-[#283618]">Hasło</h2>

                    @if (session('status') === 'password-updated')
                        <p class="mt-2 text-[13px] font-semibold text-[#3f6212]">Hasło zmienione.</p>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="{{ $label }}">Obecne hasło</label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
                            @error('current_password', 'updatePassword') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="new_password" class="{{ $label }}">Nowe hasło</label>
                                <input type="password" id="new_password" name="password" required autocomplete="new-password" class="{{ $field }}">
                            </div>
                            <div>
                                <label for="new_password_confirmation" class="{{ $label }}">Powtórz nowe hasło</label>
                                <input type="password" id="new_password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
                            </div>
                        </div>
                        @error('password', 'updatePassword') <p class="{{ $error }}">{{ $message }}</p> @enderror

                        <button type="submit" class="{{ $secondary }}">Zmień hasło</button>
                    </form>
                </section>

                {{-- ---------------------------
                     Usunięcie konta - tylko konta wyłącznie agenta (konto z rolą w panelu
                     usuwa admin; ProfileController::destroy kasuje całego użytkownika)
                     --------------------------- --}}
                @if ($agent->isAgentOnly())
                    <section class="{{ $card }}">
                        <h2 class="text-[18px] font-semibold text-[#283618]">Usuń konto</h2>
                        <p class="mt-1 text-[13px] text-[#616657]">Usuniemy konto, obszary działania i oznaczenia „Szukam”. Tego nie da się cofnąć.</p>

                        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-4" onsubmit="return confirm('Na pewno usunąć konto agenta?')">
                            @csrf
                            @method('DELETE')

                            <div>
                                <label for="delete_password" class="{{ $label }}">Potwierdź hasłem</label>
                                <input type="password" id="delete_password" name="password" required autocomplete="current-password" class="{{ $field }}">
                                @error('password', 'userDeletion') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="cursor-pointer rounded-xl border border-[#994d0a] px-4 py-2 text-[13px] font-semibold text-[#994d0a] transition hover:bg-[#fdf0e8]">Usuń konto</button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>

@endsection
