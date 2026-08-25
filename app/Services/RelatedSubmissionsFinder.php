<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalEdit;
use Illuminate\Support\Collection;

// ---------------------------
// Wyszukuje inne zgłoszenia (Animal + AnimalEdit) z tym samym adresem e-mail —
// pomocne przy moderacji do wykrywania prób "podbicia" pozycji przez wielokrotne
// dodawanie tego samego zwierzaka. Duplikat może być zarówno już zatwierdzonym
// ogłoszeniem (Animal), jak i inną oczekującą/odrzuconą propozycją (AnimalEdit).
// ---------------------------

class RelatedSubmissionsFinder
{
    public static function find(
        ?string $contactEmail,
        ?int $excludeAnimalId = null,
        ?int $excludeAnimalEditId = null,
        int $limit = 5,
    ): Collection {
        if (blank($contactEmail)) {
            return collect();
        }

        $animals = Animal::where('contact_email', $contactEmail)
            ->when($excludeAnimalId, fn ($q) => $q->where('id', '!=', $excludeAnimalId))
            ->get();

        $edits = AnimalEdit::where('contact_email', $contactEmail)
            ->when($excludeAnimalEditId, fn ($q) => $q->where('id', '!=', $excludeAnimalEditId))
            ->get();

        return $animals->concat($edits)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }
}
