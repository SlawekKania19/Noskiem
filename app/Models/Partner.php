<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Partner wspierający serwis — pozycja paska z logotypami nad stopką.
// Pasek renderuje się na każdej podstronie, więc listę trzymamy w cache'u,
// żeby nie odpytywać bazy przy każdym żądaniu.
// ---------------------------

class Partner extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'url',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    protected const CACHE_KEY = 'partners.active';

    protected static function booted(): void
    {
        // ** Każda zmiana w panelu (także drag&drop kolejności i przełącznik aktywności)
        // musi unieważnić cache paska
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));

        // Usunięcie partnera kasuje też plik logo — inaczej zostałby sierotą w storage
        static::deleting(function (Partner $partner): void {
            if (filled($partner->logo_path)) {
                Storage::disk('public')->delete($partner->logo_path);
            }
        });
    }

    // ** Adres partnera zawsze z protokołem — bez niego <a href="maxizoo.pl"> byłby
    // ścieżką względną i prowadził na noskiem.pl/maxizoo.pl. Admin może wpisać samą
    // domenę, a https:// dokładamy sami (i w formularzu, i przy każdym zapisie).
    public static function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        return preg_match('~^https?://~i', $url) ? $url : 'https://'.$url;
    }

    protected function url(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => self::normalizeUrl($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ** Aktywni partnerzy w kolejności ustawionej w panelu. Celowo tablica prostych
    // tablic, nie kolekcja modeli — cache na driverze "database" bezpieczniej trzyma
    // czyste dane niż zserializowane obiekty (tak samo robi Setting::allCached()).
    public static function forBar(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->active()
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name', 'logo_path', 'url'])
            ->map(fn (Partner $partner) => [
                'name' => $partner->name,
                'url' => $partner->url,
                'logo' => filled($partner->logo_path)
                    ? Storage::disk('public')->url($partner->logo_path)
                    : null,
            ])
            ->all());
    }
}
