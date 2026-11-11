<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Photo;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

// ---------------------------
// Generowanie plakatu ogłoszenia w PDF (dompdf).
// Szablon: resources/views/posters/animal.blade.php - stamtąd bierzemy wygląd,
// tu tylko przygotowujemy dane (zdjęcia przycięte do kadrów, kod QR, logo)
// i składamy dokument.
// ---------------------------

class PosterRenderer
{
    // ** Dozwolone formaty:
    //  - 'a4' - jeden plakat na całą kartkę A4 (pionowo)
    //  - 'a5' - ten sam plakat 2× obok siebie na kartce A4 (poziomo, do przecięcia)
    public const FORMATS = ['a4', 'a5'];

    // ** Układ zdjęć zależny od ich liczby:
    //  - 'side' (1–2 zdjęcia) - lewa kolumna obok danych: jedno w pionie 2:3 (4×6)
    //    albo dwa jedno pod drugim (~4:3, razem ta sama wysokość co pojedyncze),
    //  - 'row'  (3+) - rząd obok siebie nad danymi, kadr 4:5, maks. MAX_ROW_PHOTOS,
    //  - 'none' - brak zdjęć.
    // Kadry w pikselach (szer. × wys.) - dompdf nie obsługuje object-fit, więc przycinamy
    // po stronie PHP. Proporcje muszą zgadzać się z wymiarami w mm w szablonie.
    private const MAX_ROW_PHOTOS = 4;

    private const CROPS = [
        'single' => [800, 1200],
        'stacked' => [800, 584],
        'row' => [640, 800],
    ];

    public function render(Animal $animal, string $format): PdfInstance
    {
        $format = in_array($format, self::FORMATS, true) ? $format : 'a4';

        $showUrl = route('animals.show', $animal);
        [$photoLayout, $photos] = $this->photoDataUris($animal);

        return Pdf::loadView('posters.animal', [
            'animal' => $animal,
            'format' => $format,
            'photoLayout' => $photoLayout,
            'photosData' => $photos,
            'qrData' => $this->qrDataUri($showUrl),
            'logoData' => $this->logoDataUri(),
            'showUrl' => $showUrl,
        ])
            // ** Inter ma ~400 KB na krój - bez subsettingu PDF puchłby do kilku MB
            ->setOption('isFontSubsettingEnabled', true)
            // ** dompdf mnoży line-height przez metryki fontu (Inter ~1.21 em) i przez ten
            // współczynnik (domyślnie 1.1 z config/dompdf.php) - linie wychodziły ~1.7× wyższe
            // niż w CSS. 0.85 sprawia, że line-height w szablonie znaczy mniej więcej to, co mówi.
            ->setOption('fontHeightRatio', 0.85)
            ->setPaper('a4', $format === 'a5' ? 'landscape' : 'portrait');
    }

    // ---------------------------
    // Zdjęcia jako data-URI (base64), przycięte do kadrów z CROPS - dzięki temu
    // dompdf nie sięga po pliki ani sieć, a PDF nie zawiera zdjęć w pełnej rozdzielczości.
    // Zwraca [układ ('none'|'side'|'row'), [data-URI...]].
    // ---------------------------
    private function photoDataUris(Animal $animal): array
    {
        $disk = Storage::disk('public');

        // ** Główne na początek, potem reszta w kolejności dodania; pomijamy brakujące pliki
        $photos = $animal->photos
            ->sortByDesc(fn (Photo $photo) => (int) $photo->is_main)
            ->filter(fn (Photo $photo) => $photo->path && $disk->exists($photo->path))
            ->values();

        $manager = new ImageManager(new Driver());

        $crop = function (Photo $photo, array $size) use ($manager, $disk): ?string {
            try {
                return $this->cropToDataUri($manager, $disk->get($photo->path), ...$size);
            } catch (\Throwable $e) {
                // Uszkodzony plik nie może blokować całego plakatu
                report($e);

                return null;
            }
        };

        // ** Układ wybieramy po liczbie zdjęć PO odrzuceniu uszkodzonych - dlatego
        // najpierw próbujemy kadru rzędowego, a dla 1–2 udanych kadrujemy ponownie
        $photos = $photos->take(self::MAX_ROW_PHOTOS);

        if ($photos->count() >= 3) {
            $row = $photos->map(fn (Photo $photo) => $crop($photo, self::CROPS['row']))->filter()->values();

            if ($row->count() >= 3) {
                return ['row', $row->all()];
            }
        }

        $size = $photos->count() === 1 ? self::CROPS['single'] : self::CROPS['stacked'];
        $side = $photos->take(2)
            ->map(fn (Photo $photo) => ['photo' => $photo, 'data' => $crop($photo, $size)])
            ->filter(fn (array $item) => $item['data'] !== null)
            ->values();

        if ($side->isEmpty()) {
            return ['none', []];
        }

        // ** Udało się tylko jedno z dwóch - kadrujemy je jeszcze raz w pionie, jak pojedyncze
        if ($side->count() === 1 && $size !== self::CROPS['single']) {
            return ['side', [$crop($side[0]['photo'], self::CROPS['single'])]];
        }

        return ['side', $side->pluck('data')->all()];
    }

    // ** Kadrowanie "cover": skalujemy tak, żeby zdjęcie pokryło cały kadr, i tniemy
    // nadmiar. W pionie bierzemy wycinek nieco powyżej środka (35%) - pyszczek jest
    // zwykle w górnej części zdjęcia, a czysty środek często ucina głowę.
    private function cropToDataUri(ImageManager $manager, string $binary, int $width, int $height): string
    {
        $image = $manager->decode($binary);

        $scale = max($width / $image->width(), $height / $image->height());
        $image->scale(
            width: (int) ceil($image->width() * $scale),
            height: (int) ceil($image->height() * $scale),
        );

        $x = (int) round(($image->width() - $width) / 2);
        $y = (int) round(($image->height() - $height) * 0.35);

        return (string) $image->crop($width, $height, $x, $y)
            ->encodeUsingFileExtension('jpg', quality: 80)
            ->toDataUri();
    }

    // ** Kod QR z linkiem do ogłoszenia - zwracany od razu jako data-URI PNG
    private function qrDataUri(string $url): string
    {
        $qr = new QrCode(
            data: $url,
            size: 320,
            margin: 0,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
        );

        return (new PngWriter())->write($qr)->getDataUri();
    }

    // ** Logo (pinezka z noskiem) - ten sam rysunek co w navbarze (layouts/public.blade.php),
    // w kolorze tekstu plakatu
    private function logoDataUri(): string
    {
        $svg = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="240" height="240">
            <path d="M 120 212 C 120 212 55 140 55 94 C 55 56 84 28 120 28 C 156 28 185 56 185 94 C 185 140 120 212 120 212 Z" fill="none" stroke="#283618" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M 104 74 L 136 74 C 142 74 144 80 140 85 L 126 101 C 123 105 117 105 114 101 L 100 85 C 96 80 98 74 104 74 Z" fill="#283618"/>
            <g fill="none" stroke="#283618" stroke-width="8" stroke-linecap="round">
                <path d="M 120 104 L 120 112"/>
                <path d="M 120 112 C 120 122 110 126 102 121"/>
                <path d="M 120 112 C 120 122 130 126 138 121"/>
                <path d="M 70 78 L 90 82" stroke-width="7"/>
                <path d="M 72 98 L 91 95" stroke-width="7"/>
                <path d="M 170 78 L 150 82" stroke-width="7"/>
                <path d="M 168 98 L 149 95" stroke-width="7"/>
            </g>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
