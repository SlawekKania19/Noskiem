<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\QueryException;

// ---------------------------
// Ustawienia dopasowań po zdjęciach edytowalne z panelu (strona "Dopasowanie AI").
// Wartości zapisane w tabeli settings (klucz "match.<ścieżka>") nadpisują
// config/noskiem_match.php przy starcie aplikacji — reszta kodu dalej czyta zwykłe
// config('noskiem_match...'), nic nie wie o panelu.
// Brak wpisu w settings = obowiązuje wartość z pliku/.env ("domyślna").
// Celowo NIE ma tu enabled, python ani hf_offline — zależą od instalacji na serwerze,
// błąd z panelu mógłby wyłączyć cały moduł.
// ---------------------------

class MatchSettings
{
    public const KEY_PREFIX = 'match.';

    // ** Ścieżka w config/noskiem_match.php => typ wartości
    public const FIELDS = [
        'queue.paused' => 'bool',
        'queue.hours' => 'string',
        'queue.batch_limit' => 'int',
        'queue.max_seconds' => 'int',
        'queue.threads' => 'int',
        'queue.nice' => 'int',
        'search.threshold_possible' => 'float',
        'search.threshold_strong' => 'float',
        'search.top_n' => 'int',
        'search.filters.radius_km' => 'float',
        'search.filters.date_window_days' => 'int',
        'search.filters.opposite_status' => 'bool',
        'search.filters.same_species' => 'bool',
        'search.filters.exclude_same_email' => 'bool',
        'notify.ttl_hours' => 'int',
    ];

    // Wartości z pliku/.env sprzed nadpisania (zapamiętane przy pierwszym apply())
    protected static array $defaults = [];

    // ** Instancja configu, dla której zapamiętano $defaults — nowa instancja aplikacji
    // (np. kolejny test w tym samym procesie) zaczyna od czystych wartości domyślnych
    protected static ?\WeakReference $defaultsFor = null;

    // ---------------------------
    // Nakłada wartości z panelu na config. Wołane w AppServiceProvider::boot()
    // i po zapisie w panelu. Bezpieczne do wielokrotnego wywołania.
    // ---------------------------
    public static function apply(): void
    {
        $config = app('config');

        if (self::$defaultsFor?->get() !== $config) {
            self::$defaults = [];
            self::$defaultsFor = \WeakReference::create($config);
        }

        // ** Najpierw zapamiętujemy wartości z pliku — zanim cokolwiek je nadpisze
        foreach (array_keys(self::FIELDS) as $path) {
            if (! array_key_exists($path, self::$defaults)) {
                self::$defaults[$path] = config('noskiem_match.'.$path);
            }
        }

        try {
            foreach (self::FIELDS as $path => $type) {
                $stored = Setting::get(self::KEY_PREFIX.$path);

                config(['noskiem_match.'.$path => $stored === null
                    ? self::$defaults[$path]
                    : self::cast($stored, $type)]);
            }
        } catch (QueryException) {
            // ** Brak tabeli settings (świeża instalacja, testy przed migracją) —
            // zostają wartości z pliku
        }
    }

    // Wartości domyślne (z pliku/.env), niezależnie od tego, co ustawiono w panelu
    public static function defaults(): array
    {
        self::apply();

        return self::$defaults;
    }

    // Obecnie obowiązujące wartości (po nadpisaniu)
    public static function current(): array
    {
        return collect(self::FIELDS)
            ->mapWithKeys(fn ($type, $path) => [$path => config('noskiem_match.'.$path)])
            ->all();
    }

    // Ścieżki, które mają wartość z panelu (różną od domyślnej)
    public static function overridden(): array
    {
        return collect(array_keys(self::FIELDS))
            ->filter(fn ($path) => Setting::get(self::KEY_PREFIX.$path) !== null)
            ->values()
            ->all();
    }

    // ---------------------------
    // Zapis z panelu. Wartość równa domyślnej = usuwamy wpis, żeby późniejsza zmiana
    // w .env dalej działała (wpis w settings by ją przykrył).
    // ---------------------------
    public static function save(array $values): void
    {
        $defaults = self::defaults();

        foreach (self::FIELDS as $path => $type) {
            if (! array_key_exists($path, $values)) {
                continue;
            }

            $value = self::cast($values[$path], $type);

            if (self::same($value, self::cast($defaults[$path], $type), $type)) {
                Setting::forget(self::KEY_PREFIX.$path);
            } else {
                Setting::set(self::KEY_PREFIX.$path, self::serialize($value, $type));
            }
        }

        self::apply();
    }

    // Usuwa wszystkie nadpisania — wracają wartości z pliku/.env
    public static function reset(): void
    {
        foreach (array_keys(self::FIELDS) as $path) {
            Setting::forget(self::KEY_PREFIX.$path);
        }

        self::apply();
    }

    // ---------------------------
    // Konwersje typów (settings trzyma wszystko jako tekst)
    // ---------------------------

    protected static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            default => trim((string) $value),
        };
    }

    protected static function serialize(mixed $value, string $type): string
    {
        return $type === 'bool' ? ($value ? '1' : '0') : (string) $value;
    }

    protected static function same(mixed $a, mixed $b, string $type): bool
    {
        // ** Float porównujemy z tolerancją (0.6 z formularza vs 0.6 z .env)
        return $type === 'float' ? abs($a - $b) < 1e-9 : $a === $b;
    }
}
