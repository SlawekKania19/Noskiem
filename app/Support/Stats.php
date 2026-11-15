<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// ---------------------------
// Zapis zdarzeń do dziennika statystyk (tabela stat_events). Wołane z hooków modeli
// (Animal, AnimalEdit, Sighting, ChipRegistration, Message), więc łapie też zmiany
// z panelu admina. Błąd zapisu statystyki nigdy nie może zepsuć właściwej akcji
// (zgłoszenia, moderacji) - dlatego report() zamiast wyjątku.
// ---------------------------
class Stats
{
    // ** Typy zdarzeń z etykietami do panelu
    public const TYPES = [
        'animal_submitted'   => 'Nowe zgłoszenia (potwierdzony e-mail)',
        'animal_published'   => 'Opublikowane ogłoszenia',
        'animal_rejected'    => 'Odrzucone zgłoszenia',
        'animal_resolved'    => 'Odnalezione / zakończone',
        'animal_deleted'     => 'Usunięte ogłoszenia',
        'sighting_submitted' => 'Zgłoszenia „widziałem"',
        'chip_registered'    => 'Rejestracje chipów',
        'message_sent'       => 'Wiadomości do autorów',
    ];

    public static function record(string $type, array $attributes = []): void
    {
        try {
            DB::table('stat_events')->insert([
                'type'           => $type,
                'listing_status' => $attributes['listing_status'] ?? null,
                'species_id'     => $attributes['species_id'] ?? null,
                'voivodeship_id' => $attributes['voivodeship_id'] ?? null,
                'subject_id'     => $attributes['subject_id'] ?? null,
                'days'           => $attributes['days'] ?? null,
                'occurred_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ** Wspólne pola z ogłoszenia/zgłoszenia (Animal i AnimalEdit mają te same kolumny)
    public static function fromListing(object $listing): array
    {
        return [
            'listing_status' => $listing->status,
            'species_id'     => $listing->species_id,
            'voivodeship_id' => $listing->voivodeship_id,
            'subject_id'     => $listing->id,
        ];
    }
}
