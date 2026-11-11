<?php

use App\Models\Photo;
use Illuminate\Database\Migrations\Migration;

// ---------------------------
// Jednorazowe uzupełnienie wymiarów (photos.width/height) zdjęć dodanych przed
// wprowadzeniem kadrowania miniaturek. Bez wymiarów ręczny kadr moderatora i ramka
// z detekcji nie dają się przeliczyć na object-position - miniaturka zostaje domyślna.
// Odczyt samego nagłówka pliku (getimagesize) - szybkie, bez dekodowania obrazu.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Photo::whereNull('width')->chunkById(500, function ($photos) {
            foreach ($photos as $photo) {
                $photo->fillDimensionsFromFile();

                // ** saveQuietly - bez zdarzeń modelu i bez ruszania updated_at
                if ($photo->isDirty(['width', 'height'])) {
                    $photo->timestamps = false;
                    $photo->saveQuietly();
                }
            }
        });
    }

    public function down(): void
    {
        // Dane pomocnicze - kolumny usuwa migracja add_focus_to_photos_table
    }
};
