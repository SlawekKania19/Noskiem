<?php

namespace App\Services;

use App\Models\Animal;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Generowanie plakatu ogłoszenia w PDF (dompdf).
// Szablon: resources/views/posters/animal.blade.php — stamtąd bierzemy wygląd,
// tu tylko przygotowujemy dane (zdjęcie jako base64, kod QR) i składamy dokument.
// ---------------------------

class PosterRenderer
{
    // ** Dozwolone formaty:
    //  - 'a4' — jeden plakat na całą kartkę A4
    //  - 'b5' — ten sam plakat 2× na kartce A4 (do przecięcia na pół)
    public const FORMATS = ['a4', 'b5'];

    public function render(Animal $animal, string $format): PdfInstance
    {
        $format = in_array($format, self::FORMATS, true) ? $format : 'a4';

        $showUrl = route('animals.show', $animal);

        return Pdf::loadView('posters.animal', [
            'animal' => $animal,
            'format' => $format,
            'photoData' => $this->mainPhotoDataUri($animal),
            'qrData' => $this->qrDataUri($showUrl),
            'showUrl' => $showUrl,
        ])->setPaper('a4', 'portrait');
    }

    // ** Główne zdjęcie jako data-URI (base64) — dzięki temu dompdf nie musi
    // sięgać po plik z dysku ani po sieć podczas renderowania
    private function mainPhotoDataUri(Animal $animal): ?string
    {
        $photo = $animal->photos->firstWhere('is_main', true) ?? $animal->photos->first();

        if ($photo === null) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($photo->path)) {
            return null;
        }

        $mime = $disk->mimeType($photo->path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($photo->path));
    }

    // ** Kod QR z linkiem do ogłoszenia — zwracany od razu jako data-URI PNG
    private function qrDataUri(string $url): string
    {
        $qr = new QrCode(
            data: $url,
            size: 320,
            margin: 4,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
        );

        return (new PngWriter())->write($qr)->getDataUri();
    }
}
