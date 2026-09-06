{{-- ---------------------------
     Szablon plakatu ogłoszenia (PDF / dompdf).
     To jest miejsce do zmiany wyglądu plakatu.

     Formaty:
       - a4  → jeden plakat na całą kartkę A4
       - b5  → ten sam plakat 2× na kartce A4 (do przecięcia na pół)

     Uwaga dompdf:
       * brak flexboxa/grida, transformacji CSS itp. — layout na blokach,
         tabelach i rozmiarach w mm,
       * marginesy strony ustawiamy przez @page (natywne, pewne) zamiast
         paddingiem na kontenerze — inaczej treść wychodzi poza prawy brzeg,
       * w wariancie B5 obie połowy pozycjonujemy absolutnie, żeby nadwyżka
         treści w pierwszej połowie nie spychała drugiej na kolejną stronę.
       * font DejaVu Sans (config/dompdf.php) ma polskie znaki, ale NIE emoji.
     --------------------------- --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 15mm; size: A4 portrait; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #283618;
        }

        /* ** Pojedynczy plakat A4 — po prostu wypełnia kolumnę treści strony */
        .poster {
            width: 100%;
            page-break-inside: avoid;
        }

        /* ** Wariant B5 — dwie identyczne połowy na jednej kartce A4.
             Wrapper trzyma wysokość strony, połowy pozycjonowane absolutnie. */
        .sheet-b5 {
            position: relative;
            width: 100%;
            height: 267mm;
        }
        .sheet-b5 .poster {
            position: absolute;
            left: 0;
            width: 100%;
            height: 131mm;
            overflow: hidden;
        }
        .sheet-b5 .poster--top {
            top: 0;
            padding-bottom: 4mm;
            border-bottom: 1px dashed #b9b9a8;
        }
        .sheet-b5 .poster--bottom {
            top: 136mm;
        }

        /* ** Baner statusu */
        .banner {
            background: #994d0a;
            color: #fefae0;
            text-align: center;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 5mm 0;
            font-size: 28pt;
            border-radius: 3mm;
        }
        .banner--found { background: #3f6212; }
        .sheet-b5 .banner {
            font-size: 17pt;
            padding: 2.5mm 0;
        }

        /* ** Zdjęcie główne */
        .photo-wrap { text-align: center; margin-top: 6mm; }
        .photo-wrap img {
            max-width: 100%;
            max-height: 95mm;
            border-radius: 3mm;
        }
        .sheet-b5 .photo-wrap { margin-top: 2.5mm; }
        .sheet-b5 .photo-wrap img { max-height: 38mm; }

        /* ** Imię / tytuł */
        .name {
            font-size: 24pt;
            font-weight: bold;
            margin-top: 5mm;
        }
        .sheet-b5 .name { font-size: 13pt; margin-top: 2mm; }

        .meta {
            font-size: 11pt;
            color: #616657;
            margin-top: 1.5mm;
        }
        .sheet-b5 .meta { font-size: 7.5pt; margin-top: 1mm; }

        /* ** Sekcje opisowe (znaki szczególne, opis) */
        .section {
            font-size: 10.5pt;
            line-height: 1.45;
            margin-top: 4mm;
        }
        .section .label {
            display: block;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 1px;
            color: #8f9485;
            margin-bottom: 1mm;
        }
        .sheet-b5 .section { font-size: 7.5pt; margin-top: 2mm; line-height: 1.3; }
        .sheet-b5 .section .label { font-size: 6.5pt; }

        /* ** Blok kontaktowy */
        .contact {
            margin-top: 6mm;
            border-top: 2px solid #283618;
            padding-top: 4mm;
        }
        .sheet-b5 .contact { margin-top: 3mm; padding-top: 2mm; }

        .contact-table { width: 100%; }
        .contact-table td { vertical-align: middle; }

        .contact-label {
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 1px;
            color: #8f9485;
        }
        .contact-phone {
            font-size: 22pt;
            font-weight: bold;
            color: #994d0a;
            margin-top: 1mm;
        }
        .contact-email {
            font-size: 14pt;
            font-weight: bold;
            color: #994d0a;
            margin-top: 1mm;
            word-break: break-all;
        }
        .sheet-b5 .contact-label { font-size: 6.5pt; }
        .sheet-b5 .contact-phone { font-size: 12pt; }
        .sheet-b5 .contact-email { font-size: 9pt; }

        .qr { text-align: right; width: 34mm; }
        .qr img { width: 30mm; height: 30mm; }
        .sheet-b5 .qr { width: 20mm; }
        .sheet-b5 .qr img { width: 18mm; height: 18mm; }

        .foot {
            margin-top: 4mm;
            font-size: 8.5pt;
            color: #8f9485;
        }
        .foot .url { color: #283618; font-weight: bold; }
        .sheet-b5 .foot { font-size: 6.5pt; margin-top: 2mm; }
    </style>
</head>
<body>
    @if ($format === 'b5')
        <div class="sheet-b5">
            @include('posters.partials.poster', ['position' => 'top'])
            @include('posters.partials.poster', ['position' => 'bottom'])
        </div>
    @else
        @include('posters.partials.poster', ['position' => null])
    @endif
</body>
</html>
