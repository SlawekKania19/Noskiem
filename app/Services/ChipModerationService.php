<?php

namespace App\Services;

use App\Mail\ChipRegistrationApproved;
use App\Mail\ChipRegistrationRejected;
use App\Models\ChipRegistration;
use App\Models\ModerationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Serwis moderacji wpisów w bazie chipów. Jak SightingModerationService — zmienia
// mod_status na samym rekordzie; po zatwierdzeniu wpis jest wyszukiwalny po numerze.
// ---------------------------

class ChipModerationService
{
    /**
     * @throws \RuntimeException gdy wpis nie jest w statusie pending
     */
    public function approve(ChipRegistration $registration, int $moderatorId): ChipRegistration
    {
        if ($registration->mod_status !== 'pending') {
            throw new \RuntimeException('To zgłoszenie zostało już rozpatrzone.');
        }

        $registration->update(['mod_status' => 'approved']);

        ModerationLog::create([
            'chip_registration_id' => $registration->id,
            'action'               => 'approved',
            'user_id'              => $moderatorId,
        ]);

        // ** Mail nie może zepsuć moderatorowi kliknięcia "Zatwierdź" — błąd tylko logujemy
        try {
            Mail::to($registration->contact_email)
                ->send(new ChipRegistrationApproved($registration));
        } catch (\Throwable $e) {
            report($e);
        }

        return $registration;
    }

    /**
     * @throws \RuntimeException gdy wpis nie jest w statusie pending
     */
    public function reject(ChipRegistration $registration, string $reason, int $moderatorId): void
    {
        if ($registration->mod_status !== 'pending') {
            throw new \RuntimeException('To zgłoszenie zostało już rozpatrzone.');
        }

        $registration->update([
            'mod_status'        => 'rejected',
            'mod_reject_reason' => $reason,
        ]);

        ModerationLog::create([
            'chip_registration_id' => $registration->id,
            'action'               => 'rejected',
            'comment'              => $reason,
            'user_id'              => $moderatorId,
        ]);

        try {
            Mail::to($registration->contact_email)
                ->send(new ChipRegistrationRejected($registration, $reason));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ---------------------------
    // Edycje zatwierdzonego wpisu (pending_changes) — wpis przez cały czas pozostaje
    // "approved", zmienia się tylko jego treść
    // ---------------------------

    /**
     * @throws \RuntimeException gdy wpis nie ma oczekujących zmian
     */
    public function approveChanges(ChipRegistration $registration, int $moderatorId): ChipRegistration
    {
        $changes = $registration->pending_changes;

        if (! $changes) {
            throw new \RuntimeException('Ten wpis nie ma zmian do zatwierdzenia.');
        }

        // ** Nowa naklejka zastępuje starą — stary plik usuwamy dopiero po udanym zapisie
        $oldSticker = isset($changes['sticker_photo_path']) ? $registration->sticker_photo_path : null;

        $registration->update(array_merge(
            array_intersect_key($changes, array_flip(ChipRegistration::EDITABLE_FIELDS)),
            ['pending_changes' => null],
        ));

        if ($oldSticker) {
            Storage::disk('local')->delete($oldSticker);
        }

        ModerationLog::create([
            'chip_registration_id' => $registration->id,
            'action'               => 'approved',
            'comment'              => 'Edycja: zatwierdzono zmiany',
            'user_id'              => $moderatorId,
        ]);

        try {
            Mail::to($registration->contact_email)
                ->send(new ChipRegistrationApproved($registration, isEdit: true));
        } catch (\Throwable $e) {
            report($e);
        }

        return $registration;
    }

    /**
     * @throws \RuntimeException gdy wpis nie ma oczekujących zmian
     */
    public function rejectChanges(ChipRegistration $registration, string $reason, int $moderatorId): void
    {
        $changes = $registration->pending_changes;

        if (! $changes) {
            throw new \RuntimeException('Ten wpis nie ma zmian do zatwierdzenia.');
        }

        $registration->update(['pending_changes' => null]);

        if (! empty($changes['sticker_photo_path'])) {
            Storage::disk('local')->delete($changes['sticker_photo_path']);
        }

        ModerationLog::create([
            'chip_registration_id' => $registration->id,
            'action'               => 'rejected',
            'comment'              => 'Edycja: '.$reason,
            'user_id'              => $moderatorId,
        ]);

        // ** Mail na adres z rekordu (dotychczasowy), nie z odrzuconych zmian
        try {
            Mail::to($registration->contact_email)
                ->send(new ChipRegistrationRejected($registration, $reason, isEdit: true));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
