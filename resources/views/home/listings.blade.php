{{-- ---------------------------
     Ogłoszenia jednego statusu na stronie głównej - dwie grupy (App\Services\HomepageListings):
     "Ostatnio dodane" i losowane z wagami starsze ogłoszenia ("Wciąż szukamy").
     Parametry: $status (lost|found), $title, $rotatedTitle, $emptyText,
     $latest, $rotated (kolekcje Animal), $nearby (czy włączona okolica odwiedzającego)
     --------------------------- --}}
<div class="flex items-center justify-between">
    <h2 class="text-[20px] font-semibold text-[#283618]">{{ $title }}</h2>
    <div class="flex items-center gap-4">
        <a href="{{ route('animals.index', ['status' => $status]) }}" class="text-[13px] font-semibold text-[#283618] transition-colors hover:underline active:text-[#1e2812]">
            Zobacz wszystkie
        </a>
        <a href="{{ route('map.index', ['status' => $status]) }}" class="text-[13px] font-semibold text-[#283618] transition-colors hover:underline active:text-[#1e2812]">
            Zobacz na mapie
        </a>
    </div>
</div>

{{-- ** Grupa 1: najnowsze --}}
<h3 class="mt-6 text-[12px] font-semibold uppercase tracking-wide text-[#8f9485]">Ostatnio dodane</h3>

<div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    @forelse ($latest as $animal)
        <x-animal-card :animal="$animal" />
    @empty
        <p class="col-span-full text-center text-[14px] text-[#8f9485]">{{ $emptyText }}</p>
    @endforelse
</div>

{{-- ** Grupa 2: starsze ogłoszenia z losowania - tylko gdy są jakieś poza "Ostatnio dodane" --}}
@if ($rotated->isNotEmpty())
    <div class="mt-10 flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
        <h3 class="text-[12px] font-semibold uppercase tracking-wide text-[#8f9485]">{{ $rotatedTitle }}</h3>

        {{-- ** Okolica odwiedzającego: stan (włączona / nie) renderuje serwer z ciasteczka,
             Alpine tylko obsługuje kliknięcia (homeLocation w home.blade.php) --}}
        <div x-data="homeLocation()" class="text-[12px] text-[#616657]">
            @if ($nearby)
                Częściej pokazujemy ogłoszenia z Twojej okolicy
                ·
                <button type="button" @click="disable()" class="cursor-pointer font-semibold text-[#283618] hover:underline">Wyłącz</button>
            @else
                <button type="button" @click="enable()" :disabled="busy" class="inline-flex cursor-pointer items-center gap-1 font-semibold text-[#283618] hover:underline disabled:cursor-wait disabled:opacity-60">
                    {{-- Heroicons: map-pin (mini) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                    </svg>
                    <span x-text="busy ? 'Ustalam lokalizację...' : 'Pokazuj częściej z mojej okolicy'">Pokazuj częściej z mojej okolicy</span>
                </button>
            @endif
            <p x-show="error" x-cloak x-text="error" class="mt-1 text-[#994d0a]"></p>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($rotated as $animal)
            <x-animal-card :animal="$animal" />
        @endforeach
    </div>
@endif
