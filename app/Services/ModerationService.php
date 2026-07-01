<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\ModerationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ---------------------------
// Serwis moderacji ogłoszeń.
// Obsługuje zatwierdzanie i odrzucanie zgłoszeń z AnimalEdit,
// przenoszenie zdjęć oraz logowanie akcji moderatora.
// Wywoływany z Filament Actions (AnimalEditResource).
// ---------------------------

class ModerationService
{
    // ---------------------------
    // Zatwierdzenie zgłoszenia
    // ---------------------------

    /**
     * Zatwierdza AnimalEdit.
     * Dla nowych ogłoszeń (animal_id = null) tworzy rekord Animal.
     * Dla edycji istniejących — aktualizuje powiązany Animal.
     * Przenosi zdjęcia z animal_edit_id na animal_id.
     *
     * @throws \RuntimeException gdy zgłoszenie nie jest w statusie pending
     */
    public function approve(AnimalEdit $edit, int $moderatorId): Animal
    {
        if ($edit->mod_status !== 'pending') {
            throw new \RuntimeException('To zgłoszenie zostało już rozpatrzone.');
        }

        return DB::transaction(function () use ($edit, $moderatorId): Animal {

            // ** Nowe ogłoszenie — Animal jeszcze nie istnieje
            if ($edit->animal_id === null) {
                $animal = Animal::create([
                    'status'         => $edit->status,
                    'title'          => $edit->title,
                    'description'    => $edit->description,
                    'animal_name'    => $edit->animal_name,
                    'ident_marks'    => $edit->ident_marks,
                    'chip_present'   => $edit->chip_present,
                    'chip_number'    => $edit->chip_number,
                    'species_id'     => $edit->species_id,
                    'breed_id'       => $edit->breed_id,
                    'date_event'     => $edit->date_event,
                    'voivodeship_id' => $edit->voivodeship_id,
                    'city_id'        => $edit->city_id,
                    'location_text'  => $edit->location_text,
                    'latitude'       => $edit->latitude,
                    'longitude'      => $edit->longitude,
                    'contact_name'   => $edit->contact_name,
                    'contact_email'  => $edit->contact_email,
                    'contact_phone'  => $edit->contact_phone,
                    'edit_token'     => $edit->edit_token ?? Str::uuid(),
                ]);
            } else {
                // ** Edycja istniejącego ogłoszenia
                $animal = $edit->animal;
                $animal->update($edit->only([
                    'status', 'title', 'description', 'animal_name', 'ident_marks',
                    'chip_present', 'chip_number', 'species_id', 'breed_id',
                    'date_event', 'voivodeship_id', 'city_id', 'location_text',
                    'latitude', 'longitude', 'contact_name', 'contact_email', 'contact_phone',
                ]));
            }

            // Przeniesienie zdjęć z tymczasowego edit_id na właściwy animal_id
            foreach ($edit->photos as $photo) {
                $photo->update([
                    'animal_id'      => $animal->id,
                    'animal_edit_id' => null,
                ]);
            }

            $edit->update(['mod_status' => 'approved']);

            ModerationLog::create([
                'animal_id'      => $animal->id,
                'animal_edit_id' => $edit->id,
                'action'         => 'approved',
                'user_id'        => $moderatorId,
            ]);

            return $animal;
        });
    }

    // ---------------------------
    // Odrzucenie zgłoszenia
    // ---------------------------

    /**
     * Odrzuca AnimalEdit z podanym powodem.
     * animal_id może być null gdy to nowe ogłoszenie (Animal jeszcze nie istnieje).
     *
     * @throws \RuntimeException gdy zgłoszenie nie jest w statusie pending
     */
    public function reject(AnimalEdit $edit, string $reason, int $moderatorId): void
    {
        if ($edit->mod_status !== 'pending') {
            throw new \RuntimeException('To zgłoszenie zostało już rozpatrzone.');
        }

        $edit->update([
            'mod_status'        => 'rejected',
            'mod_reject_reason' => $reason,
        ]);

        ModerationLog::create([
            'animal_id'      => $edit->animal_id, // nullable — nowe ogłoszenia nie mają jeszcze Animal
            'animal_edit_id' => $edit->id,
            'action'         => 'rejected',
            'comment'        => $reason,
            'user_id'        => $moderatorId,
        ]);
    }

    // ---------------------------
    // Diff między AnimalEdit a Animal
    // ---------------------------

    /**
     * Zwraca tablicę zmienionych pól między AnimalEdit a powiązanym Animal.
     * Format: ['pole' => ['old' => ..., 'new' => ...]]
     * Gdy brak powiązanego Animal (nowe ogłoszenie) — zwraca ['type' => 'new'].
     */
    public function diff(AnimalEdit $edit): array
    {
        if ($edit->animal_id === null) {
            return ['type' => 'new'];
        }

        $animal = $edit->animal;

        $fields = [
            'status', 'title', 'description', 'animal_name', 'ident_marks',
            'chip_present', 'chip_number', 'species_id', 'breed_id',
            'date_event', 'voivodeship_id', 'city_id', 'location_text',
            'latitude', 'longitude', 'contact_name', 'contact_email', 'contact_phone',
        ];

        $diff = [];

        foreach ($fields as $field) {
            if ($animal->$field != $edit->$field) {
                $diff[$field] = [
                    'old' => $animal->$field,
                    'new' => $edit->$field,
                ];
            }
        }

        // Diff zdjęć
        $animalPhotos = $animal->photos->pluck('path')->toArray();
        $editPhotos   = $edit->photos->pluck('path')->toArray();

        if ($animalPhotos !== $editPhotos) {
            $diff['photos'] = [
                'old' => $animalPhotos,
                'new' => $editPhotos,
            ];
        }

        return $diff;
    }
}
