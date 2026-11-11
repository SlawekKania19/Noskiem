{{-- ---------------------------
     Podgląd karty ogłoszenia dla panelu (App\Http\Controllers\Admin\CardPreviewController).
     Te same style i ta sama czcionka co strona - karta wygląda 1:1 jak na stronie głównej.
     Szerokości jak w gridzie strony głównej: desktop (4 kolumny w max-w-7xl) ok. 292 px,
     telefon (2 kolumny, ekran ~390 px) ok. 171 px.
     Tryb embed (okno "Kadry miniaturek"): sama karta desktopowa, kadr zdjęcia zmieniany
     na żywo wiadomością postMessage z panelu - bez przeładowania ramki.
     --------------------------- --}}
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Podgląd karty</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-[Inter,ui-sans-serif,system-ui,sans-serif] antialiased text-[#283618] bg-white">
    @if ($embed)
        {{-- ** pointer-events-none - podgląd, link karty nie ma tu sensu --}}
        <div id="card" class="pointer-events-none w-[292px] p-2">
            <x-animal-card :animal="$animal" />
        </div>

        <script>
            // ** Nowy kadr z okna "Kadry miniaturek" (photo-focus-field.blade.php).
            // Tylko z naszej domeny - panel i podgląd działają pod tym samym adresem
            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin || event.data?.type !== 'noskiem-card-focus') {
                    return;
                }

                const img = document.querySelector('#card img');

                if (img) {
                    img.style.objectPosition = event.data.position;
                }
            });
        </script>
    @else
        <div class="flex flex-wrap items-start gap-6 p-3">
            <div>
                <p class="mb-2 text-[11px] uppercase tracking-wide text-[#8f9485]">Komputer</p>
                <div class="pointer-events-none w-[292px]">
                    <x-animal-card :animal="$animal" />
                </div>
            </div>

            <div>
                <p class="mb-2 text-[11px] uppercase tracking-wide text-[#8f9485]">Telefon</p>
                <div class="pointer-events-none w-[171px]">
                    <x-animal-card :animal="$animal" />
                </div>
            </div>
        </div>
    @endif
</body>
</html>
