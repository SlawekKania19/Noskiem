@php
    // ---------------------------
    // Pojedynczy plakat (tylko ogłoszenia "Zaginął" — patrz PosterController).
    // Renderowany raz (A4) lub dwa razy obok siebie (A5, $position 'left'/'right').
    // Dane wejściowe: $animal, $photoLayout ('none'|'side'|'row'), $photosData (data-URI[]),
    // $qrData, $logoData, $showUrl, $position
    // ---------------------------

    // ** Wariant kompaktowy — połówka kartki w formacie A5
    $compact = $position !== null;

    // ** Nagłówek "ZAGINĄŁ KOT" — dla gatunku "Inne" neutralne "ZWIERZAK"
    $speciesName = $animal->species?->name_pl;
    $headline = 'ZAGINĄŁ '.mb_strtoupper($speciesName && $speciesName !== 'Inne' ? $speciesName : 'zwierzak');

    // ** Pod nagłówkiem imię + rasa (bez "Rasa nieznana" — nic nie wnosi na plakacie)
    $breedName = $animal->breed?->breed_pl;
    $breedName = $breedName && $breedName !== 'Rasa nieznana' ? $breedName : null;

    // ** Gdzie: opis miejsca + miejscowość (jeśli opis jej jeszcze nie zawiera) + województwo
    $cityName = $animal->city?->name_pl;
    $location = collect([
        $animal->location_text,
        $cityName && ! str_contains(mb_strtolower((string) $animal->location_text), mb_strtolower($cityName)) ? $cityName : null,
    ])->filter()->implode(', ');
    if ($animal->voivodeship?->name_pl) {
        $location = trim($location.' (woj. '.mb_strtolower($animal->voivodeship->name_pl).')');
    }

    // ** Teksty skracane, żeby dane zmieściły się nad boxem kontaktu — na plakacie
    // i tak liczy się zdjęcie + kontakt. Limity sprawdzone na najgorszym przypadku
    // (rząd 4 zdjęć + długie znaki/zachowanie/miejsce).
    $limit = fn (?string $text, int $chars) => \Illuminate\Support\Str::limit((string) $text, $chars);
    $description = $limit($animal->description, $compact ? 260 : 450);
    $identMarks = $limit($animal->ident_marks, $compact ? 90 : 200);
    $behavior = $limit($animal->behavior, 100);

    // ** Komórki danych: [etykieta, wartość, marker]
    $colors = ['Kolor', $animal->colors->pluck('name')->implode(', '), null];
    $chip = ['Chip', $animal->chip_present ? 'tak' : 'nie', null];
    $marks = ['Znaki szczególne', $identMarks, null];
    $behaviorCell = ['Zachowanie', $compact ? null : $behavior, null]; // w A5 bez "Zachowania"
    $date = ['Data zaginięcia', $animal->date_event?->format('d.m.Y'), null];
    $where = ['Gdzie', $location, 'dot'];
    $desc = ['Opis', $description, null];

    // ** Układ boczny (1–2 zdjęcia) — wąska kolumna obok zdjęć, więc prawie wszystko
    // pod sobą. Pozostałe układy — pełna szerokość, dwie kolumny jak w projekcie.
    $rows = $photoLayout === 'side'
        ? [[$colors, $chip], [$marks], [$behaviorCell], [$date], [$where], [$desc]]
        : [[$colors, $chip], [$marks], [$behaviorCell], [$date, $where], [$desc]];

    // Puste komórki i wiersze pomijamy
    $rows = array_values(array_filter(array_map(
        fn (array $row) => array_values(array_filter($row, fn (array $cell) => filled($cell[1]))),
        $rows,
    )));

    // ---------------------------
    // Wymiary zdjęć w mm — proporcje zgodne z kadrami w PosterRenderer::CROPS
    // (single 2:3, stacked 800×584, row 4:5)
    // ---------------------------
    $contentWidth = $compact ? 130.5 : 184;
    $gap = $compact ? 2.2 : 3;
    $sideCol = $compact ? 56 : 84;                  // szerokość lewej kolumny ze zdjęciami
    $sideGutter = $compact ? 4.5 : 6;               // odstęp zdjęcia od tekstu
    $textCol = $contentWidth - $sideCol - $sideGutter; // obie kolumny jawnie — inaczej dompdf rozdziela miejsce po swojemu
    $sideHeight = $sideCol * 1.5;                   // pojedyncze zdjęcie 2:3
    $stackedHeight = ($sideHeight - $gap) / 2;      // dwa jedno pod drugim = ta sama wysokość
    $rowCount = count($photosData);
    $rowWidth = $rowCount ? ($contentWidth - $gap * ($rowCount - 1)) / $rowCount : 0;
    $mm = fn (float $value) => round($value, 2).'mm';

    // ** Telefon w formacie z projektu ("600 123 456") — polski kierunkowy pomijamy
    $phone = $animal->formatted_phone
        ? str_replace('-', ' ', preg_replace('/^\+48 /', '', $animal->formatted_phone))
        : null;

    // ** Adres ogłoszenia bez schematu
    $displayUrl = preg_replace('#^https?://#', '', $showUrl);
@endphp

<div class="poster {{ $compact ? 'is-a5 '.$position : '' }}">
    <div class="poster-top">
        <table class="head">
            <tr>
                <td><span class="badge">ZAGINIONY</span></td>
                <td class="brand"><img src="{{ $logoData }}" alt=""><span>noskiem.org</span></td>
            </tr>
        </table>

        <div class="headline">{{ $headline }}</div>

        @if ($animal->animal_name || $breedName)
            <div class="subline">
                @if ($animal->animal_name)<span class="name">{{ $animal->animal_name }}</span>@endif
                @if ($breedName)<span class="breed">&nbsp;{{ $breedName }}</span>@endif
            </div>
        @endif

        @if ($photoLayout === 'side')
            {{-- ** 1–2 zdjęcia: lewa kolumna ze zdjęciami, dane po prawej --}}
            <table class="media">
                <tr>
                    <td class="media-col" style="width: {{ $mm($sideCol) }};">
                        @foreach ($photosData as $photo)
                            <img
                                class="photo"
                                src="{{ $photo }}"
                                alt=""
                                style="width: {{ $mm($sideCol) }}; height: {{ $mm(count($photosData) === 1 ? $sideHeight : $stackedHeight) }};{{ $loop->first ? '' : ' margin-top: '.$mm($gap).';' }}"
                            >
                        @endforeach
                    </td>
                    <td class="text-col" style="width: {{ $mm($textCol) }}; padding-left: {{ $mm($sideGutter) }};">
                        @include('posters.partials.details', ['rows' => $rows, 'class' => 'details--side'])
                    </td>
                </tr>
            </table>
        @else
            @if ($photoLayout === 'row')
                {{-- ** 3+ zdjęć: rząd obok siebie, dane pod spodem --}}
                <table class="photo-row">
                    <tr>
                        @foreach ($photosData as $photo)
                            <td style="{{ $loop->first ? '' : 'padding-left: '.$mm($gap).';' }}">
                                <img class="photo" src="{{ $photo }}" alt="" style="width: {{ $mm($rowWidth) }}; height: {{ $mm($rowWidth * 1.25) }};">
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endif

            @include('posters.partials.details', ['rows' => $rows])
        @endif
    </div>

    <div class="poster-bottom">
        <div class="contact">
            <table>
                <tr>
                    <td>
                        {{-- ** Bez imienia/nazwiska i bez e-maila — tylko telefon, a gdy autor
                             go nie podał, link do ogłoszenia (kontakt przez formularz na stronie) --}}
                        @if ($phone)
                            <div class="contact-label">Widziałeś? Zadzwoń:</div>
                            <div class="contact-phone">{{ $phone }}</div>
                        @else
                            <div class="contact-label">Widziałeś? Napisz przez ogłoszenie:</div>
                            <div class="contact-url">{{ $displayUrl }}</div>
                        @endif
                    </td>
                    <td class="qr-cell">
                        <span class="qr-box"><img src="{{ $qrData }}" alt=""></span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="promo">
            <table>
                <tr>
                    <td class="promo-icon"><img src="{{ $logoData }}" alt=""></td>
                    <td class="promo-text">
                        Szukasz swojego psa lub kota? Znalazłeś zwierzaka? Sprawdź bazę zaginionych
                        i znalezionych zwierząt z całej Polski na <b>noskiem.org</b>.
                    </td>
                </tr>
            </table>
        </div>

        <table class="foot">
            <tr>
                <td>Ogłoszenie nr {{ $animal->id }} · {{ $displayUrl }}</td>
                <td class="thanks">Dziękujemy za pomoc!</td>
            </tr>
        </table>
    </div>
</div>
