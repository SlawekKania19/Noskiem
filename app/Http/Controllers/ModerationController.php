<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\ModerationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    /**
     * Zatwierdzenie zgłoszenia (nowe ogłoszenie lub edycja).
     */
    public function approve(AnimalEdit $edit)
    {
        DB::transaction(function () use ($edit) {

            // NOWE OGŁOSZENIE
            if ($edit->animal_id === null) {

                $animal = Animal::create([
                    'status'        => $edit->status,
                    'title'         => $edit->title,
                    'description'   => $edit->description,
                    'animal_name'   => $edit->animal_name,
                    'ident_marks'   => $edit->ident_marks,
                    'chip_present'  => $edit->chip_present,
                    'chip_number'   => $edit->chip_number,
                    'species_id'    => $edit->species_id,
                    'breed_id'      => $edit->breed_id,
                    'date_event'    => $edit->date_event,
                    'voivodeship_id' => $edit->voivodeship_id,
                    'city_id'       => $edit->city_id,
                    'location_text' => $edit->location_text,
                    'latitude'      => $edit->latitude,
                    'longitude'     => $edit->longitude,
                    'contact_name'  => $edit->contact_name,
                    'contact_email' => $edit->contact_email,
                    'contact_phone' => $edit->contact_phone,
                ]);

                // Przeniesienie zdjęć
                foreach ($edit->photos as $photo) {
                    $photo->update([
                        'animal_id' => $animal->id,
                        'animal_edit_id' => null,
                    ]);
                }

                $edit->update(['mod_status' => 'approved']);

                ModerationLog::create([
                    'animal_id' => $animal->id,
                    'animal_edit_id' => $edit->id,
                    'action' => 'approved_new',
                ]);

                return;
            }

            // EDYCJA ISTNIEJĄCEGO OGŁOSZENIA
            $animal = $edit->animal;

            $animal->update([
                'status'        => $edit->status,
                'title'         => $edit->title,
                'description'   => $edit->description,
                'animal_name'   => $edit->animal_name,
                'ident_marks'   => $edit->ident_marks,
                'chip_present'  => $edit->chip_present,
                'chip_number'   => $edit->chip_number,
                'species_id'    => $edit->species_id,
                'breed_id'      => $edit->breed_id,
                'date_event'    => $edit->date_event,
                'voivodeship_id' => $edit->voivodeship_id,
                'city_id'       => $edit->city_id,
                'location_text' => $edit->location_text,
                'latitude'      => $edit->latitude,
                'longitude'     => $edit->longitude,
                'contact_name'  => $edit->contact_name,
                'contact_email' => $edit->contact_email,
                'contact_phone' => $edit->contact_phone,
            ]);

            // Przeniesienie zdjęć
            foreach ($edit->photos as $photo) {
                $photo->update([
                    'animal_id' => $animal->id,
                    'animal_edit_id' => null,
                ]);
            }

            $edit->update(['mod_status' => 'approved']);

            ModerationLog::create([
                'animal_id' => $animal->id,
                'animal_edit_id' => $edit->id,
                'action' => 'approved_edit',
            ]);
        });

        return response()->json([
            'message' => 'Zgłoszenie zostało zatwierdzone.',
        ]);
    }

    /**
     * Odrzucenie zgłoszenia.
     */
    public function reject(AnimalEdit $edit)
    {
        $edit->update(['mod_status' => 'rejected']);

        ModerationLog::create([
            'animal_id' => $edit->animal_id,
            'animal_edit_id' => $edit->id,
            'action' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Zgłoszenie zostało odrzucone.',
        ]);
    }

    /**
     * Porównanie pól (diff) między Animal a AnimalEdit.
     */
    public function diff(AnimalEdit $edit)
    {
        if ($edit->animal_id === null) {
            return response()->json([
                'type' => 'new',
                'message' => 'To jest nowe ogłoszenie, nie edycja.',
            ]);
        }

        $animal = $edit->animal;

        $fields = [
            'status', 'title', 'description', 'animal_name', 'ident_marks',
            'chip_present', 'chip_number', 'species_id', 'breed_id',
            'date_event', 'voivodeship_id', 'city_id', 'location_text',
            'latitude', 'longitude', 'contact_name', 'contact_email', 'contact_phone'
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

        return $diff;
    }
}
