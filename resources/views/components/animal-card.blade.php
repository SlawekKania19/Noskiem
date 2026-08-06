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

    // ** Na miniaturce mało miejsca na pełny adres — pokazujemy tylko dwie ostatnie części
    // (np. "Skaryszewska 40, Wola, Warszawa" -> "Wola, Warszawa"), pełna wersja w tooltipie
    $locationParts = $locationLabel ? array_map('trim', explode(',', $locationLabel)) : [];
    $cardLocationLabel = count($locationParts) > 2
        ? implode(', ', array_slice($locationParts, -2))
        : $locationLabel;

    // ** Tytuł karty liczony na bieżąco z aktualnego szablonu (Ustawienia), nie z zapisanej
    // kolumny `title` — zmiana szablonu w panelu jest od razu widoczna na wszystkich kartach
    $cardTitle = $animal->generated_title;
@endphp

<a
    href="{{ route('animals.show', $animal) }}"
    class="group block overflow-hidden rounded-2xl bg-white shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] transition hover:shadow-[0px_6px_18px_0px_rgba(30,38,18,0.12)] active:transform-[scale(0.98)]"
>
    {{-- ** Miniaturka zdjęcia (główne z tabeli photos, placeholder gdy brak) — aspect-square zamiast
         stałej wysokości 140px, bo przy zmiennej szerokości karty w gridzie (1/2/3 kolumny) dawało to
         bardzo płaskie kadry i obcinało dużą część zdjęcia (szczególnie pionowych, z telefonu) --}}
    <div class="relative aspect-square w-full overflow-hidden bg-[#dbe3d1]">
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
        <p class="mt-2 truncate text-[15px] font-semibold text-[#1e2612]" title="{{ $cardTitle }}">
            {{ $cardTitle }}
        </p>

        {{-- ** Rasa (jeśli podana) --}}
        @if ($animal->breed)
            <p class="truncate text-[12px] text-[#616657]">{{ $animal->breed->breed_pl }}</p>
        @endif

        {{-- ** Lokalizacja — skrócona do dwóch ostatnich części, pełna w tooltipie --}}
        @if ($locationLabel)
            <p class="mt-1 truncate text-[11px] text-[#8f9485]" title="{{ $locationLabel }}">📍 {{ $cardLocationLabel }}</p>
        @endif
    </div>
</a>
