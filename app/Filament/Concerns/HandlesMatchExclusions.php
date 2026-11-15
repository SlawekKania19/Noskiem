<?php

namespace App\Filament\Concerns;

use App\Services\MatchExclusions;
use Filament\Notifications\Notification;

// ---------------------------
// Przycisk "Nie pokazuj" / "Przywróć" przy kandydacie w oknie "Podobne (AI)"
// (filament/match-preview-row.blade.php). Strony podglądu zgłoszenia i ogłoszenia.
// Okno przelicza się po kliknięciu samo - treść modala renderuje się przy każdym odświeżeniu.
// ---------------------------
trait HandlesMatchExclusions
{
    public function toggleMatchExclusion(int $candidateId): void
    {
        $user = auth()->user();

        abort_unless($user?->is_admin || $user?->is_moderator, 403);

        $excluded = app(MatchExclusions::class)->toggle($this->record, $candidateId, $user->id);

        Notification::make()
            ->title($excluded ? 'Propozycja odrzucona - nie trafi do maila' : 'Propozycja przywrócona')
            ->success()
            ->send();
    }
}
