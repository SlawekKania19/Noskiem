<?php

namespace App\Services;

use App\Mail\SightingApproved;
use App\Mail\SightingRejected;
use App\Models\ModerationLog;
use App\Models\Sighting;
use Illuminate\Support\Facades\Mail;

// ---------------------------
// Serwis moderacji zgłoszeń "też widziałem" (Sighting). Prostszy niż
// ModerationService dla ogłoszeń — sighting nie tworzy osobnego Animal, tylko
// zmienia mod_status na sobie samym i po zatwierdzeniu staje się widoczny jako
// wpis w timeline pod oryginalnym ogłoszeniem.
// ---------------------------

class SightingModerationService
{
    /**
     * @throws \RuntimeException gdy zgłoszenie nie jest w statusie pending
     */
    public function approve(Sighting $sighting, int $moderatorId): Sighting
    {
        if ($sighting->mod_status !== 'pending') {
            throw new \RuntimeException('To zgłoszenie zostało już rozpatrzone.');
        }

        $sighting->update(['mod_status' => 'approved']);

        ModerationLog::create([
            'animal_id'    => $sighting->animal_id,
            'sighting_id'  => $sighting->id,
            'action'       => 'approved',
            'user_id'      => $moderatorId,
        ]);

        // ** Mail nie może zepsuć moderatorowi kliknięcia "Zatwierdź" — błąd tylko logujemy
        try {
            Mail::to($sighting->contact_email, $sighting->contact_name)
                ->send(new SightingApproved($sighting));
        } catch (\Throwable $e) {
            report($e);
        }

        return $sighting;
    }

    /**
     * @throws \RuntimeException gdy zgłoszenie nie jest w statusie pending
     */
    public function reject(Sighting $sighting, string $reason, int $moderatorId): void
    {
        if ($sighting->mod_status !== 'pending') {
            throw new \RuntimeException('To zgłoszenie zostało już rozpatrzone.');
        }

        $sighting->update([
            'mod_status'        => 'rejected',
            'mod_reject_reason' => $reason,
        ]);

        ModerationLog::create([
            'animal_id'    => $sighting->animal_id,
            'sighting_id'  => $sighting->id,
            'action'       => 'rejected',
            'comment'      => $reason,
            'user_id'      => $moderatorId,
        ]);

        try {
            Mail::to($sighting->contact_email, $sighting->contact_name)
                ->send(new SightingRejected($sighting));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
