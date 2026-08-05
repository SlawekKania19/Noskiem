@php
    // ---------------------------
    // Karta ogłoszenia (Figma: node 22:275, "Karta — Burek")
    // ---------------------------

    // ** Główne zdjęcie (is_main), a gdy brak — pierwsze dostępne
    $mainPhoto = $animal->photos->firstWhere('is_main', true) ?? $animal->photos->first();

    // ** Etykieta i kolor plakietki statusu (status: lost/found)
    $statusLabels = [
        'lost' => 'Zaginiony',
        'found' => 'Znaleziony',
    ];
    $statusStyles = [
        'lost' => 'bg-[#fcecd1] text-[#994d0a]',
        'found' => 'bg-[#dbe9d8] text-[#3f6212]',
    ];
    $statusLabel = $statusLabels[$animal->status] ?? $animal->status;
    $statusStyle = $statusStyles[$animal->status] ?? 'bg-[#eee] text-[#616657]';

    // ** Lokalizacja — preferujemy tekst podany przez zgłaszającego, potem nazwę miasta
    $locationLabel = $animal->location_text ?: ($animal->city->name_pl ?? null);

    // ** Tytuł karty — imię zwierzaka, a gdy brak (np. zwierzę bez chipa/imienia) — tytuł ogłoszenia
    $cardTitle = $animal->animal_name ?: $animal->title;
@endphp

<a
    href="{{ route('animals.show', $animal) }}"
    class="group block overflow-hidden rounded-2xl bg-white shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] transition hover:shadow-[0px_6px_18px_0px_rgba(30,38,18,0.12)] active:scale-[0.98]"
>
    {{-- ** Miniaturka zdjęcia (główne z tabeli photos, placeholder gdy brak) --}}
    <div class="relative h-[140px] w-full overflow-hidden bg-[#dbe3d1]">
        @if ($mainPhoto)
            <img
                src="{{ asset('storage/'.$mainPhoto->path) }}"
                alt="{{ $cardTitle }}"
                class="h-full w-full object-cover"
                loading="lazy"
            >
        @else
            <div class="flex h-full items-center justify-center text-[28px]">🐾</div>
        @endif
    </div>

    <div class="p-3">
        {{-- ** Plakietka statusu --}}
        <span class="inline-flex rounded-md px-2 py-1 text-[10px] font-semibold uppercase tracking-wide {{ $statusStyle }}">
            {{ $statusLabel }}
        </span>

        {{-- ** Tytuł --}}
        <p class="mt-2 truncate text-[15px] font-semibold text-[#1e2612]">
            {{ $cardTitle }}
        </p>

        {{-- ** Rasa (jeśli podana) --}}
        @if ($animal->breed)
            <p class="truncate text-[12px] text-[#616657]">{{ $animal->breed->breed_pl }}</p>
        @endif

        {{-- ** Lokalizacja --}}
        @if ($locationLabel)
            <p class="mt-1 truncate text-[11px] text-[#8f9485]">📍 {{ $locationLabel }}</p>
        @endif
    </div>
</a>
