<?php

namespace App\Http\Controllers;

use App\Mail\NewSightingForModeration;
use App\Mail\SightingSubmissionReceived;
use App\Models\Animal;
use App\Models\Sighting;
use App\Models\User;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// ---------------------------
// Kontroler zgłoszeń "też widziałem" pod ogłoszeniem. Dostępny tylko dla
// zatwierdzonych ogłoszeń ze statusem "found" — dla "lost" ten formularz nie
// ma zastosowania (jest już formularz kontaktowy do autora). Flow identyczne
// jak przy zgłoszeniu ogłoszenia: potwierdzenie e-mail -> moderacja ->
// zatwierdzenie/odrzucenie (patrz SightingModerationService).
// ---------------------------

class SightingController extends Controller
{
    public function create(Animal $animal)
    {
        abort_unless($animal->status === 'found' && $animal->mod_status === 'approved', 404);

        return view('sightings.create', ['animal' => $animal]);
    }

    public function store(Request $request, Animal $animal)
    {
        abort_unless($animal->status === 'found' && $animal->mod_status === 'approved', 404);

        // ** Honeypot — pole niewidoczne dla ludzi, ale boty je wypełniają. Udajemy sukces,
        // żeby bot nie wiedział, że został złapany, i nie próbował omijać zabezpieczenia
        if ($request->filled('website')) {
            return redirect()->route('animals.show', $animal)
                ->with('success', 'Zgłoszenie zostało zapisane. Sprawdź swoją skrzynkę e-mail i potwierdź adres, żeby trafiło do moderacji.');
        }

        $data = $request->validate([
            'description'   => 'required|string',
            'date_seen'     => 'required|date',
            'location'      => 'required|string|max:255',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'contact_name'  => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'photos'        => 'nullable|array|max:6',
            'photos.*'      => 'image|max:5120',
            'accept_terms'  => 'accepted',
        ]);

        $photos = $data['photos'] ?? [];
        unset($data['photos'], $data['accept_terms']);

        // ** Gatunek dziedziczony z ogłoszenia — to zgłoszenie dotyczy tego samego zwierzaka
        $data['animal_id']    = $animal->id;
        $data['species_id']   = $animal->species_id;
        $data['mod_status']   = 'pending';
        $data['edit_token']   = (string) Str::uuid();
        $data['submitter_ip'] = $request->ip();

        $sighting = Sighting::create($data);

        foreach ($photos as $photo) {
            $path = ImageCompressor::store($photo, 'photos', 'public');

            $sighting->photos()->create(['path' => $path]);
        }

        try {
            Mail::to($sighting->contact_email, $sighting->contact_name)
                ->send(new SightingSubmissionReceived($sighting));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('animals.show', $animal)
            ->with('success', "Zgłoszenie zostało zapisane. Sprawdź skrzynkę {$sighting->contact_email} i potwierdź adres, żeby trafiło do moderacji.");
    }

    // Potwierdza adres e-mail zgłaszającego (link z maila SightingSubmissionReceived).
    // Dopiero po tym moderatorzy dostają powiadomienie — ochrona przed botami i
    // fałszywymi adresami, tak samo jak przy zgłoszeniu ogłoszenia.
    public function confirmEmail(Sighting $sighting, Request $request)
    {
        if ($request->get('token') !== $sighting->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu.');
        }

        if ($sighting->email_verified_at === null) {
            $sighting->update(['email_verified_at' => now()]);

            $moderators = User::where('is_admin', true)->orWhere('is_moderator', true)->get();

            foreach ($moderators as $moderator) {
                try {
                    Mail::to($moderator->email, $moderator->name)
                        ->send(new NewSightingForModeration($sighting));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('animals.show', $sighting->animal_id)
            ->with('success', 'Dziękujemy za potwierdzenie! Twoje zgłoszenie trafiło do moderacji.');
    }
}
