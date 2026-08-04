@props([
    'voivodeships',
    'voivodeshipName' => 'voivodeship_id',
    'cityName' => 'city_id',
    'selectedVoivodeshipId' => null,
    'selectedCityId' => null,
    'selectedCityLabel' => null,
    'required' => true,
    'showLabels' => true,
    'voivodeshipLabel' => 'Województwo',
    'cityLabel' => 'Miejscowość',
    'voivodeshipPlaceholder' => 'Wybierz województwo',
    'cityPlaceholder' => 'Wpisz min. 3 litery nazwy miejscowości',
    'fieldClass' => 'mt-1 w-full rounded-xl border border-[#e5e5dc] px-3 py-2 text-[14px] text-[#283618] focus:border-[#283618] focus:outline-hidden',
    'labelClass' => 'text-[12px] uppercase tracking-wide text-[#8f9485]',
])

{{-- ---------------------------
     Wybór województwa + miejscowości z autouzupełnianiem (rejestr SIMC, ~100 tys. rekordów).
     "contents" nie tworzy własnego elementu w layoucie — oba pola zostają zwykłymi
     komórkami rodzica (np. gridu), a x-data spina je wspólnym stanem Alpine.
     --------------------------- --}}
<div class="contents" x-data="cityPicker({
        voivodeshipId: @js((string) ($selectedVoivodeshipId ?? '')),
        cityId: @js((string) ($selectedCityId ?? '')),
        cityName: @js($selectedCityLabel ?? ''),
    })"
>
    <div>
        @if ($showLabels)
            <label class="{{ $labelClass }}">{{ $voivodeshipLabel }}</label>
        @endif
        <select
            name="{{ $voivodeshipName }}"
            x-model="voivodeshipId"
            @change="onVoivodeshipChange()"
            @if ($required) required @endif
            class="{{ $fieldClass }}"
        >
            <option value="">{{ $voivodeshipPlaceholder }}</option>
            @foreach ($voivodeships as $v)
                <option value="{{ $v->id }}">{{ $v->name_pl }}</option>
            @endforeach
        </select>
        @error($voivodeshipName)
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
    </div>

    <div class="relative">
        @if ($showLabels)
            <label class="{{ $labelClass }}">{{ $cityLabel }}</label>
        @endif
        <input
            type="text"
            x-model="query"
            @input="onQueryInput()"
            @focus="if (results.length) open = true"
            @click.outside="open = false"
            :disabled="!voivodeshipId"
            autocomplete="off"
            @if ($required) required @endif
            placeholder="{{ $cityPlaceholder }}"
            class="{{ $fieldClass }} disabled:cursor-not-allowed disabled:bg-[#f5f5f0]"
        >
        <input type="hidden" name="{{ $cityName }}" :value="cityId">

        <ul
            x-show="open"
            x-cloak
            class="absolute z-10 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-[#e5e5dc] bg-white py-1 shadow-lg"
        >
            <template x-for="city in results" :key="city.id">
                <li
                    @click="select(city)"
                    class="cursor-pointer px-3 py-2 text-[14px] text-[#283618] hover:bg-[#f5f5f0]"
                    x-text="city.name_pl"
                ></li>
            </template>
        </ul>

        @error($cityName)
            <p class="mt-1 text-[12px] text-[#994d0a]">{{ $message }}</p>
        @enderror
    </div>
</div>
