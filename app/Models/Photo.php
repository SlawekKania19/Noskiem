<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Model reprezentujący zdjęcie zwierzęcia.
// ---------------------------

class Photo extends Model
{
    // ** Pionowe położenie kadru, gdy nie znamy położenia zwierzaka (brak detekcji / stare
    // zdjęcie): nieco powyżej środka - pyszczek jest zwykle w górnej części zdjęcia
    public const DEFAULT_FOCUS_Y = 0.3;

    // ** Zapas nad ramką zwierzęcia (ułamek wysokości zdjęcia), gdy całe się nie mieści -
    // żeby nie ciąć tuż nad uszami
    private const HEADROOM = 0.04;

    protected $fillable = [
        'animal_id',
        'animal_edit_id',
        'sighting_id',
        'path',
        'width',
        'height',
        'focus',
        'focus_source',
        'focus_manual',
        'is_main',
    ];

    // ** Casty

    protected $casts = [
        'is_main' => 'boolean',
        'focus' => 'array',
        'focus_manual' => 'array',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected static function booted(): void
    {
        // ** Wymiary pliku przy zapisie - potrzebne do przeliczenia kadru miniaturki
        static::creating(function (Photo $photo): void {
            if (! $photo->width && filled($photo->path)) {
                $photo->fillDimensionsFromFile();
            }
        });

        // ** Usunięcie rekordu kasuje też plik z dysku - inaczej zostawałyby sieroty
        // zajmujące miejsce w storage. Dotyczy każdego usunięcia przez Eloquent
        // (panel admina, relation manager); kaskady FK w bazie omijają ten hook,
        // dlatego Animal sprząta swoje zdjęcia sam (patrz Animal::booted()).
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

    // ** Wektory do dopasowań po zdjęciach (po jednym na wersję modelu)
    public function embeddings(): HasMany
    {
        return $this->hasMany(PhotoEmbedding::class);
    }

    // ---------------------------
    // Kadrowanie miniaturek
    // ---------------------------

    public function fillDimensionsFromFile(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($this->path)) {
            return;
        }

        $size = @getimagesize($disk->path($this->path));

        if ($size) {
            [$this->width, $this->height] = $size;
        }
    }

    // ---------------------------
    // Wynik detekcji zwierzęcia z noskiem_match (box w ułamkach 0..1, size [szer, wys]).
    // Ręczny kadr moderatora (focus_manual) zostaje nietknięty i dalej ma pierwszeństwo.
    // Nie zapisuje - wołający robi save().
    // ---------------------------
    public function applyDetection(?array $box, ?array $size): void
    {
        if (is_array($size) && count($size) === 2 && ! $this->width) {
            [$this->width, $this->height] = array_map('intval', $size);
        }

        $box = self::sanitizeBox($box);
        $this->focus = $box;
        $this->focus_source = $box ? 'auto' : 'none';
    }

    // ** Ręczny punkt moderatora (ułamki 0..1) - miniaturka centruje się dokładnie na nim.
    // null = powrót do kadru automatycznego
    public function setManualFocus(?float $x, ?float $y): void
    {
        // ** Bez wymiarów punktu nie da się przeliczyć na kadr (starsze zdjęcia,
        // przed detekcją) - uzupełniamy je z pliku przy okazji
        if (! $this->width && filled($this->path)) {
            $this->fillDimensionsFromFile();
        }

        $this->focus_manual = $x === null || $y === null ? null : [
            round(max(0.0, min(1.0, $x)), 4),
            round(max(0.0, min(1.0, $y)), 4),
        ];
    }

    // ** Obszar, wokół którego kadrujemy: punkt moderatora (jako ramka zerowa) albo ramka z detekcji
    public function effectiveFocusBox(): ?array
    {
        $manual = $this->focus_manual;

        if (is_array($manual) && count($manual) === 2) {
            return self::sanitizeBox([$manual[0], $manual[1], $manual[0], $manual[1]]);
        }

        return self::sanitizeBox($this->focus);
    }

    // ** Wartość CSS object-position dla kontenera o proporcjach $ratio (szer./wys., 1 = kwadrat)
    public function objectPosition(float $ratio = 1.0): string
    {
        [$x, $y] = $this->focusPosition($ratio);

        return round($x * 100, 1).'% '.round($y * 100, 1).'%';
    }

    // ---------------------------
    // Położenie kadru [x, y] w ułamkach 0..1 - semantyka object-position: 0 = przyklejony
    // do lewej/górnej krawędzi zdjęcia, 1 = do prawej/dolnej. Używane przez miniaturki (CSS)
    // i plakat PDF (wycinanie pikseli).
    // ---------------------------
    public function focusPosition(float $ratio = 1.0): array
    {
        $box = $this->effectiveFocusBox();

        if (! $box || ! $this->width || ! $this->height || $ratio <= 0) {
            return [0.5, self::DEFAULT_FOCUS_Y];
        }

        // ** Jaki ułamek szerokości/wysokości zdjęcia widać w kontenerze przy object-fit: cover
        $imageRatio = $this->width / $this->height;
        $visibleX = $imageRatio > $ratio ? $ratio / $imageRatio : 1.0;
        $visibleY = $imageRatio < $ratio ? $imageRatio / $ratio : 1.0;

        return [
            self::axisPosition($box[0], $box[2], $visibleX, headFirst: false),
            self::axisPosition($box[1], $box[3], $visibleY, headFirst: true),
        ];
    }

    // ---------------------------
    // Pozycja kadru w jednej osi. Ramka mieści się w widocznym wycinku -> centrujemy na niej.
    // Nie mieści się -> w pionie pokazujemy górę ramki (głowa), w poziomie środek.
    // ---------------------------
    private static function axisPosition(float $from, float $to, float $visible, bool $headFirst): float
    {
        if ($visible >= 0.999) {
            return 0.5; // W tej osi nic nie jest ucinane
        }

        $offset = ($to - $from) <= $visible || ! $headFirst
            ? ($from + $to) / 2 - $visible / 2
            : $from - self::HEADROOM;

        $offset = max(0.0, min(1.0 - $visible, $offset));

        return $offset / (1.0 - $visible);
    }

    // ** [x1, y1, x2, y2] w zakresie 0..1 z x1<=x2, y1<=y2 - albo null
    private static function sanitizeBox(mixed $box): ?array
    {
        if (! is_array($box) || count($box) !== 4) {
            return null;
        }

        $box = array_map(fn ($v) => round(max(0.0, min(1.0, (float) $v)), 4), array_values($box));

        return $box[0] <= $box[2] && $box[1] <= $box[3] ? $box : null;
    }
}
