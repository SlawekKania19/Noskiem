<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\ModerationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ---------------------------
// KONTROLER MODERACJI
// ---------------------------

class ModerationController extends Controller
{
    // ** Pobranie listy zgłoszeń oczekujących na moderację 
        public function pending()
    {
        return AnimalEdit::where('mod_status', 'pending')
            ->with(['species', 'breed', 'voivodeship', 'city', 'animal', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }    

    // ** Zatwierdzenie zgłoszenia (nowe ogłoszenie lub edycja istniejącego)
    public function approve(AnimalEdit $edit)
    {
        if ($edit->mod_status !== 'pending') {
            return response()->json([
                'message' => 'To zgłoszenie zostało już rozpatrzone.'
            ], 400); // Zapobiegamy ponownemu zatwierdzeniu lub odrzuceniu tego samego zgłoszenia
        }

        DB::transaction(function () use ($edit) {

            

    // NOWE OGŁOSZENIE
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
            'action' => 'approved',
            'user_id' => 1 // UWAGA!!! Tylko do testów, w prawdziwej aplikacji powinna być to ID moderatora, który zatwierdził zgłoszenie
        ]);

        return;
    }


            // EDYCJA ISTNIEJĄCEGO OGŁOSZENIA
            $animal = $edit->animal;

            $animal->update($edit->only([
                'status', 'title', 'description', 'animal_name', 'ident_marks',
                'chip_present', 'chip_number', 'species_id', 'breed_id',
                'date_event', 'voivodeship_id', 'city_id', 'location_text',
                'latitude', 'longitude', 'contact_name', 'contact_email', 'contact_phone'
            ]));

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

        return redirect()
            ->route('moderation.pending')
            ->with('success', 'Zgłoszenie zostało zatwierdzone.');

    }

    /**
     * Odrzucenie zgłoszenia.
     */
    public function reject(Request $request, AnimalEdit $edit)
    {
        if ($edit->mod_status !== 'pending') {
            return response()->json([
                'message' => 'To zgłoszenie zostało już rozpatrzone.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $edit->update([
            'mod_status' => 'rejected',
            'mod_reject_reason' => $request->reason,
        ]);

        ModerationLog::create([
            'animal_id' => $edit->animal_id,
            'animal_edit_id' => $edit->id,
            'action' => 'rejected',
            'details' => $request->reason,
        ]);

        return redirect()
            ->route('moderation.pending')
            ->with('error', 'Zgłoszenie zostało odrzucone.');
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

        // Diff zdjęć
        $animalPhotos = $animal->photos->pluck('path')->toArray();
        $editPhotos = $edit->photos->pluck('path')->toArray();

        if ($animalPhotos !== $editPhotos) {
            $diff['photos'] = [
                'old' => $animalPhotos,
                'new' => $editPhotos,
            ];
        }

        return $diff;
    }

}
