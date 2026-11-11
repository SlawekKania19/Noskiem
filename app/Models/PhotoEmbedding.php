<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Wektor zdjęcia (DINOv2) dla danej wersji modelu - patrz noskiem_match/ i
// App\Services\PhotoEmbeddingService. vector = surowe bajty float32 (little-endian).
// ---------------------------

class PhotoEmbedding extends Model
{
    protected $fillable = [
        'photo_id',
        'model_version',
        'vector',
        'dim',
        'crop_found',
        'error',
        'duration_ms',
    ];

    // ** Wektor nigdy w JSON - to 3 KB binarnych danych bez znaczenia dla odbiorcy
    protected $hidden = ['vector'];

    protected $casts = [
        'crop_found' => 'boolean',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    // ** Wektor jako tablica floatów (do testów i diagnostyki; wyszukiwanie liczy Python)
    public function floats(): array
    {
        return $this->vector === null ? [] : array_values(unpack('g*', $this->vector));
    }
}
