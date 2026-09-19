<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Model reprezentujący zdjęcie zwierzęcia.
// ---------------------------

class Photo extends Model
{
    protected $fillable = [
        'animal_id',
        'animal_edit_id',
        'sighting_id',
        'path',
        'is_main',
    ];

    // ** Casty

    protected $casts = [
        'is_main' => 'boolean',
    ];

    // ** Usunięcie rekordu kasuje też plik z dysku — inaczej zostawałyby sieroty
    // zajmujące miejsce w storage. Dotyczy każdego usunięcia przez Eloquent
    // (panel admina, relation manager); kaskady FK w bazie omijają ten hook,
    // dlatego Animal sprząta swoje zdjęcia sam (patrz Animal::booted()).
    protected static function booted(): void
    {
        static::deleting(function (Photo $photo): void {
            if (filled($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }

    // ** Relacje

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
    public function animalEdit()
    {
        return $this->belongsTo(\App\Models\AnimalEdit::class, 'animal_edit_id');
    }

    public function sighting(): BelongsTo
    {
        return $this->belongsTo(Sighting::class);
    }
}
