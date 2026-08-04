<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// ---------------------------
// Ustawienia aplikacji w formie klucz-wartość, edytowalne z panelu Filament
// (App\Filament\Pages\Settings). Wartości są cache'owane, żeby uniknąć
// odpytywania bazy przy każdym renderze strony.
// ---------------------------

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected const CACHE_KEY = 'settings';

    // Zwraca wartość ustawienia o podanym kluczu, a gdy brak — wartość domyślną
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::allCached()[$key] ?? $default;
    }

    // Zapisuje (lub tworzy) ustawienie i czyści cache
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }

    // Wszystkie ustawienia jako zwykła tablica [klucz => wartość], z cache.
    // Celowo tablica, nie Collection — obiekty w cache'u (driver "database") potrafią się
    // uszkodzić przy (un)serializacji, zwykła tablica jest tu dużo bezpieczniejsza.
    protected static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }
}
