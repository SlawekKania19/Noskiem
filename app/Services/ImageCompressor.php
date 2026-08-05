<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

// ---------------------------
// Kompresuje i skaluje zdjęcia zgłoszeń przed zapisem na dysk — bez tego
// telefony potrafią wysyłać zdjęcia po kilkanaście MB, co szybko zapchałoby storage.
// ---------------------------

class ImageCompressor
{
    // ** Zdjęcie w galerii i tak nie jest wyświetlane w pełnej rozdzielczości aparatu
    private const MAX_DIMENSION = 1920;

    private const QUALITY = 75;

    // Zapisuje przesłany plik jako przeskalowany i skompresowany JPG, zwraca ścieżkę względną na dysku
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->decodePath($file->getRealPath())
            ->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        $path = $directory.'/'.Str::random(40).'.jpg';

        Storage::disk($disk)->put(
            $path,
            (string) $image->encodeUsingFileExtension('jpg', quality: self::QUALITY)
        );

        return $path;
    }
}
