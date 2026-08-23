<?php

namespace App\Services;

// ---------------------------
// Formatuje numer telefonu do czytelnej postaci niezależnie od tego, jak wpisał go
// autor ogłoszenia (spacje, myślniki, nawiasy itp.).
// ---------------------------

class PhoneFormatter
{
    // ** Standardowa długość polskiego numeru krajowego (bez kierunkowego) —
    // wszystko ponad te cyfry przy "+"/"00" traktujemy jako kierunkowy kraju
    private const NATIONAL_LENGTH = 9;

    // Zwraca numer w formacie "+48 600-123-456" (z kierunkowym, jeśli podano)
    // albo "600-123-456" (bez kierunkowego), grupując cyfry po 3 i łącząc myślnikami
    public static function format(?string $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        $raw = trim($raw);
        $startsWithZeroZero = str_starts_with($raw, '00');
        $hasCountryCode = str_starts_with($raw, '+') || $startsWithZeroZero;
        $digits = preg_replace('/\D/', '', $raw);

        if ($digits === '') {
            return null;
        }

        $countryCode = null;

        if ($hasCountryCode) {
            // ** "00" to alternatywny zapis kierunkowego zamiast "+" — po wyciągnięciu
            // samych cyfr trzeba go osobno usunąć, bo w przeciwieństwie do "+" jest cyfrą
            if ($startsWithZeroZero && strlen($digits) > self::NATIONAL_LENGTH + 2) {
                $digits = substr($digits, 2);
            }

            if (strlen($digits) > self::NATIONAL_LENGTH) {
                $countryCode = substr($digits, 0, -self::NATIONAL_LENGTH);
                $digits = substr($digits, -self::NATIONAL_LENGTH);
            }
        }

        $grouped = implode('-', str_split($digits, 3));

        return $countryCode ? "+{$countryCode} {$grouped}" : $grouped;
    }
}
