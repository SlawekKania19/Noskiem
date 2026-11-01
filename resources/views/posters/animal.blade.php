{{-- ---------------------------
     Szablon plakatu ogłoszenia (PDF / dompdf). Projekt: docs/poster/*.pdf
     To jest miejsce do zmiany wyglądu plakatu.

     Formaty:
       - a4  → jeden plakat na całą kartkę A4 (pionowo)
       - a5  → ten sam plakat 2× obok siebie na kartce A4 (poziomo), pionowa linia cięcia

     Uwaga dompdf:
       * brak flexboxa/grida, transformacji CSS itp. — layout na blokach,
         tabelach i rozmiarach w mm,
       * plakat ma stałą wysokość; dolny blok (kontakt, promocja, stopka) jest
         przypięty absolutnie do dołu, a górna część ma overflow: hidden — długi
         opis nigdy nie zepchnie plakatu na drugą stronę,
       * font-weight rozróżnia tylko normal/bold, dlatego Medium i SemiBold Intera
         to osobne rodziny (InterMedium, InterSemi),
       * Inter nie ma znaków ✂ i ● — te bierzemy z DejaVu Sans,
       * zdjęcia przychodzą już przycięte do kadrów (PosterRenderer), bo dompdf
         nie obsługuje object-fit. Układ zależy od liczby zdjęć: 1–2 w lewej
         kolumnie obok danych, 3+ w rzędzie nad danymi (partials/poster.blade.php).
     --------------------------- --}}
@php
    // ** Ścieżki do fontów z ukośnikami — dompdf na Windowsie nie lubi "\"
    $font = fn (string $file) => str_replace('\\', '/', resource_path('fonts/pdf/'.$file));
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        /* ** Marginesy strony zero, a plakat pozycjonowany absolutnie w mm — dompdf
             nakłada "* { margin: 0 }" także na stronę i gubi marginesy z @page */
        @page { margin: 0; size: A4 {{ $format === 'a5' ? 'landscape' : 'portrait' }}; }

        @font-face { font-family: 'Inter'; font-weight: normal; src: url('{{ $font('Inter-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: bold; src: url('{{ $font('Inter-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'InterMedium'; font-weight: normal; src: url('{{ $font('Inter-Medium.ttf') }}') format('truetype'); }
        @font-face { font-family: 'InterSemi'; font-weight: normal; src: url('{{ $font('Inter-SemiBold.ttf') }}') format('truetype'); }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
            color: #283618;
        }

        .dejavu { font-family: 'DejaVu Sans', sans-serif; }

        /* ---------------------------
           Kontener plakatu pozycjonowany absolutnie na stronie.
           A4: marginesy 12 × 13 mm; A5: dwie połówki kartki poziomej, marginesy 9 mm.
           --------------------------- */
        .poster { position: absolute; top: 12mm; left: 13mm; width: 184mm; height: 273mm; }
        .poster-top { height: 212mm; overflow: hidden; }
        .poster-bottom { position: absolute; left: 0; right: 0; bottom: 0; }

        .poster.is-a5 { top: 9mm; width: 130.5mm; height: 192mm; }
        .poster.is-a5.left { left: 9mm; }
        .poster.is-a5.right { left: 157.5mm; }
        .is-a5 .poster-top { height: 144mm; }

        /* ** Linia cięcia między połówkami A5 */
        .cut-line { position: absolute; left: 148.5mm; top: 0; height: 210mm; border-left: 1px dashed #b9b9a8; }
        .cut-label {
            position: absolute; left: 138.5mm; top: 2mm; width: 20mm;
            text-align: center; font-size: 7pt; color: #8f9485; background: #fff;
        }

        /* ** Nagłówek: etykieta statusu + logo */
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: middle; }
        .badge {
            display: inline-block;
            background: #fcecd1; color: #994d0a;
            font-weight: bold; font-size: 10pt; letter-spacing: 1.2px;
            padding: 1.8mm 3.6mm; border-radius: 2mm;
        }
        .brand { text-align: right; font-family: 'InterMedium'; font-size: 13pt; white-space: nowrap; }
        .brand img { width: 8.5mm; height: 8.5mm; vertical-align: middle; margin-right: 0.8mm; }
        .brand span { vertical-align: middle; }

        .headline { font-weight: bold; font-size: 46pt; line-height: 1.05; margin-top: 4.5mm; letter-spacing: -0.5px; }
        .subline { margin-top: 1mm; line-height: 1.2; }
        .subline .name { font-family: 'InterMedium'; font-size: 24pt; }
        .subline .breed { font-size: 16pt; color: #616657; }

        /* ** Zdjęcia — wymiary w mm ustawiane inline w partialu (zależą od liczby zdjęć) */
        .photo { display: block; border-radius: 3mm; }

        /* ** Układ boczny (1–2 zdjęcia): lewa kolumna ze zdjęciami, dane po prawej */
        .media { border-collapse: collapse; margin-top: 5mm; }
        .media td { vertical-align: top; }

        /* ** Rząd zdjęć (3+) nad danymi */
        .photo-row { border-collapse: collapse; margin-top: 5mm; }
        .photo-row td { vertical-align: top; }

        /* ** Siatka danych */
        .details { width: 100%; border-collapse: collapse; margin-top: 4.5mm; table-layout: fixed; }
        .details--side { margin-top: 0; }
        .details td { vertical-align: top; padding: 0 4mm 2.4mm 0; width: 50%; }
        .label { font-size: 7.5pt; line-height: 1; letter-spacing: 0.8px; color: #616657; text-transform: uppercase; }
        .value { font-size: 11pt; line-height: 1.25; }
        .dot { color: #d9468f; font-size: 9pt; }

        /* ** Box kontaktowy */
        .contact { background: #283618; border-radius: 4mm; padding: 5mm 6mm; }
        .contact table { width: 100%; border-collapse: collapse; }
        .contact td { vertical-align: middle; }
        .contact-label { font-size: 13pt; color: #dbe3d1; }
        .contact-phone { font-weight: bold; font-size: 34pt; color: #fff; line-height: 1.05; margin-top: 1mm; }
        .contact-url { font-weight: bold; font-size: 17pt; color: #fff; line-height: 1.25; margin-top: 1.5mm; }
        .qr-cell { width: 30mm; text-align: right; }
        .qr-box { display: inline-block; background: #fff; padding: 2.2mm; border-radius: 2.5mm; }
        .qr-box img { display: block; width: 23mm; height: 23mm; }

        /* ** Box promujący bazę */
        .promo { background: #dfe8d6; border-radius: 3mm; padding: 3.2mm 5mm; margin-top: 3.5mm; }
        .promo table { width: 100%; border-collapse: collapse; }
        .promo td { vertical-align: middle; }
        .promo-icon { width: 13mm; }
        .promo-icon img { display: block; width: 10mm; height: 10mm; }
        .promo-text { font-size: 10pt; line-height: 1.45; }

        /* ** Stopka */
        .foot { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        .foot td { font-size: 8pt; color: #8f9485; }
        .foot .thanks { text-align: right; white-space: nowrap; }

        /* ---------------------------
           Wariant A5 — te same elementy, mniejsze
           --------------------------- */
        .is-a5 .badge { font-size: 7.5pt; padding: 1.3mm 2.6mm; border-radius: 1.5mm; }
        .is-a5 .brand { font-size: 10pt; }
        .is-a5 .brand img { width: 6.5mm; height: 6.5mm; }
        .is-a5 .headline { font-size: 34pt; margin-top: 3mm; }
        .is-a5 .subline .name { font-size: 18pt; }
        .is-a5 .subline .breed { font-size: 12pt; }
        .is-a5 .photo { border-radius: 2.2mm; }
        .is-a5 .media, .is-a5 .photo-row { margin-top: 3.5mm; }
        .is-a5 .details { margin-top: 3mm; }
        .is-a5 .details--side { margin-top: 0; }
        .is-a5 .details td { padding-bottom: 1.5mm; }
        .is-a5 .label { font-size: 5.8pt; }
        .is-a5 .value { font-size: 8.5pt; }
        .is-a5 .dot { font-size: 7pt; }
        .is-a5 .contact { padding: 3.6mm 4.5mm; border-radius: 3mm; }
        .is-a5 .contact-label { font-size: 10pt; }
        .is-a5 .contact-phone { font-size: 26pt; }
        .is-a5 .contact-url { font-size: 12pt; }
        .is-a5 .qr-cell { width: 22mm; }
        .is-a5 .qr-box { padding: 1.6mm; border-radius: 2mm; }
        .is-a5 .qr-box img { width: 17mm; height: 17mm; }
        .is-a5 .promo { padding: 2.2mm 4mm; margin-top: 2.5mm; border-radius: 2.5mm; }
        .is-a5 .promo-icon { width: 10mm; }
        .is-a5 .promo-icon img { width: 7.5mm; height: 7.5mm; }
        .is-a5 .promo-text { font-size: 7.5pt; line-height: 1.35; }
        .is-a5 .foot { margin-top: 2.5mm; }
        .is-a5 .foot td { font-size: 6.2pt; }
    </style>
</head>
<body>
    @if ($format === 'a5')
        <div class="cut-line"></div>
        <div class="cut-label"><span class="dejavu">✂</span> tu przetnij</div>
        @include('posters.partials.poster', ['position' => 'left'])
        @include('posters.partials.poster', ['position' => 'right'])
    @else
        @include('posters.partials.poster', ['position' => null])
    @endif
</body>
</html>
