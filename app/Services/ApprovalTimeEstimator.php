<?php

namespace App\Services;

use App\Models\AnimalEdit;
use App\Models\Setting;

// ---------------------------
// Liczy średni czas moderacji (od zgłoszenia do zatwierdzenia) na podstawie
// ostatnich N zatwierdzonych NOWYCH ogłoszeń (bez edycji już istniejących —
// te mają inną dynamikę moderacji). Wynik używany w mailu potwierdzającym
// zgłoszenie, żeby dać zgłaszającemu orientacyjny czas oczekiwania.
// ---------------------------
class ApprovalTimeEstimator
{
    // Zwraca gotowy do wyświetlenia tekst ("3 godziny" / "2 dni") albo null,
    // gdy nie ma jeszcze żadnych zatwierdzonych zgłoszeń do policzenia średniej.
    public static function humanAverage(): ?string
    {
        $sampleSize = (int) Setting::get('approval_time_sample_size', '10');

        $edits = AnimalEdit::where('mod_status', 'approved')
            ->whereNull('animal_id')
            ->whereNotNull('approved_at')
            ->orderByDesc('approved_at')
            ->limit(max(1, $sampleSize))
            ->get(['created_at', 'approved_at']);

        if ($edits->isEmpty()) {
            return null;
        }

        // ** absolute: true jawnie — domyślne zachowanie diffInSeconds w Carbon 3 zwraca
        // różnicę ze znakiem (created_at -> approved_at to różnica ujemna, bo approved_at jest później)
        $avgSeconds = $edits->avg(fn (AnimalEdit $edit) => $edit->approved_at->diffInSeconds($edit->created_at, absolute: true));

        if ($avgSeconds < 24 * 3600) {
            $hours = max(1, (int) round($avgSeconds / 3600));

            return $hours.' '.self::polishPlural($hours, 'godzina', 'godziny', 'godzin');
        }

        $days = max(1, (int) round($avgSeconds / 86400));

        return $days.' '.self::polishPlural($days, 'dzień', 'dni', 'dni');
    }

    // Odmiana polskich rzeczowników przez liczbę (reguła CLDR dla języka polskiego)
    private static function polishPlural(int $n, string $one, string $few, string $many): string
    {
        if ($n === 1) {
            return $one;
        }

        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 >= 2 && $mod10 <= 4 && ! ($mod100 >= 12 && $mod100 <= 14)) {
            return $few;
        }

        return $many;
    }
}
