@php
    // ---------------------------
    // Pojedynczy plakat. Renderowany raz (A4) lub dwa razy (B5).
    // Dane wejściowe: $animal, $photoData (base64|null), $qrData (base64), $showUrl, $position
    // ---------------------------

    // ** Wariant kompaktowy — plakat B5 (renderowany 2× z $position 'top'/'bottom')
    $compact = $position !== null;

    $statusWord = $animal->status === 'found' ? 'ZNALEZIONO' : 'ZAGINĄŁ';
    $bannerClass = $animal->status === 'found' ? 'banner banner--found' : 'banner';

    // ** Nagłówek plakatu — imię zwierzaka, a jak brak, to wygenerowany tytuł ogłoszenia
    $headline = $animal->animal_name ?: $animal->generated_title;

    // ** Wiersz cech: gatunek, rasa, kolory
    $metaParts = array_filter([
        $animal->species?->name_pl,
        $animal->breed?->breed_pl,
        $animal->colors->isNotEmpty() ? $animal->colors->pluck('name')->implode(', ') : null,
    ]);

    $location = $animal->location_text ?: $animal->city?->name_pl;
    $dateEvent = $animal->date_event?->locale('pl')->translatedFormat('d F Y');

    // ** Opis skracany — na plakacie i tak liczy się zdjęcie + kontakt.
    // W wariancie B5 (mało miejsca) tniemy mocniej.
    $description = \Illuminate\Support\Str::limit((string) $animal->description, $compact ? 150 : 320);

    // ** Adres ogłoszenia bez schematu (ładniej wygląda pod kodem QR)
    $displayUrl = preg_replace('#^https?://#', '', $showUrl);
@endphp

<div class="poster {{ $position ? 'poster--'.$position : '' }}">
    <div class="{{ $bannerClass }}">{{ $statusWord }}</div>

    @if ($photoData)
        <div class="photo-wrap">
            <img src="{{ $photoData }}" alt="">
        </div>
    @endif

    <div class="name">{{ $headline }}</div>

    @if ($metaParts)
        <div class="meta">{{ implode(' · ', $metaParts) }}</div>
    @endif

    @if ($location || $dateEvent)
        <div class="meta">
            @if ($location) Miejsce: {{ $location }} @endif
            @if ($location && $dateEvent) &nbsp;&nbsp;•&nbsp;&nbsp; @endif
            @if ($dateEvent) Data: {{ $dateEvent }} @endif
        </div>
    @endif

    @if ($animal->ident_marks)
        <div class="section">
            <span class="label">Znaki szczególne</span>
            {{ $animal->ident_marks }}
        </div>
    @endif

    @if ($description)
        <div class="section">
            <span class="label">Opis</span>
            {{ $description }}
        </div>
    @endif

    <div class="contact">
        <table class="contact-table">
            <tr>
                <td>
                    {{-- ** Bez imienia/nazwiska — na plakacie tylko dane do kontaktu.
                         Telefon jako główny kontakt; gdy autor go nie podał — sam e-mail. --}}
                    <div class="contact-label">Kontakt</div>
                    @if ($animal->formatted_phone)
                        <div class="contact-phone">{{ $animal->formatted_phone }}</div>
                    @elseif ($animal->contact_email)
                        <div class="contact-email">{{ $animal->contact_email }}</div>
                    @endif
                </td>
                <td class="qr">
                    <img src="{{ $qrData }}" alt="">
                </td>
            </tr>
        </table>
        <div class="foot">
            Zobacz ogłoszenie online: <span class="url">{{ $displayUrl }}</span><br>
            noskiem.org — „Znajdziemy go noskiem”
        </div>
    </div>
</div>
