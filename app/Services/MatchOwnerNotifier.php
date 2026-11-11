<?php

namespace App\Services;

use App\Mail\NewSimilarListing;
use App\Models\Animal;
use App\Models\MatchOwnerNotice;
use App\Models\MatchSearch;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

// ---------------------------
// Druga strona dopasowania: po wysłaniu maila do autora NOWEGO ogłoszenia
// (PhotoMatchNotifier::process) piszemy też do autorów STARYCH ogłoszeń z listy
// "pojawiło się nowe ogłoszenie podobne do Twojego".
// Ten mail jest nieproszony, więc z limitami:
// - tylko zatwierdzone, aktywne ogłoszenia, bez wypisanych (match_notify_opt_out_at)
// - jedna para ogłoszeń = najwyżej jeden mail
// - jedno ogłoszenie = najwyżej jeden mail na owners_cooldown_days dni; kolejne
//   dopasowania w tym czasie tylko zapisujemy w logu (status "cooldown")
// ---------------------------

class MatchOwnerNotifier
{
    /**
     * @param  array  $shown  wyniki z PhotoMatchService::search() ponad progiem
     * @return int liczba wysłanych maili
     */
    public function notify(Animal $query, array $shown, MatchSearch $search): int
    {
        if (! config('noskiem_match.notify.owners_enabled') || $shown === []) {
            return 0;
        }

        $owners = Animal::whereIn('id', array_column($shown, 'listing_id'))
            ->where('mod_status', 'approved')
            ->whereNull('match_notify_opt_out_at')
            ->get()
            ->keyBy('id');

        $since = now()->subDays(max(0, (int) config('noskiem_match.notify.owners_cooldown_days')));
        $sent = 0;

        foreach ($shown as $row) {
            $owner = $owners[$row['listing_id']] ?? null;

            if ($owner === null || blank($owner->contact_email) || $this->sameAuthor($owner, $query)) {
                continue;
            }

            // ** Ta para już była (np. ponowne zatwierdzenie po edycji) - drugi raz nie piszemy
            if (MatchOwnerNotice::where('animal_id', $owner->id)->where('query_animal_id', $query->id)->exists()) {
                continue;
            }

            $recent = MatchOwnerNotice::where('animal_id', $owner->id)
                ->where('status', 'sent')
                ->where('created_at', '>=', $since)
                ->exists();

            // ** Błąd jednego maila nie blokuje pozostałych ani maila do autora nowego ogłoszenia
            try {
                if (! $recent) {
                    Mail::to($owner->contact_email, $owner->contact_name)
                        ->send(new NewSimilarListing($owner, $this->item($query, $row, $search), $this->links($owner)));
                    $sent++;
                }

                // ** Zapis PO wysyłce - "sent" w logu znaczy, że mail naprawdę wyszedł
                MatchOwnerNotice::create([
                    'match_search_id' => $search->id,
                    'animal_id'       => $owner->id,
                    'query_animal_id' => $query->id,
                    'level'           => $row['level'],
                    'status'          => $recent ? 'cooldown' : 'sent',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }

    // ** Ta sama osoba po obu stronach (np. filtr exclude_same_email wyłączony do testów)
    protected function sameAuthor(Animal $owner, Animal $query): bool
    {
        return mb_strtolower(trim((string) $owner->contact_email)) === mb_strtolower(trim((string) $query->contact_email));
    }

    // ** Dane nowego ogłoszenia do maila. Link przez podpisany endpoint kliknięć
    // (zapis zdarzenia, potem przekierowanie) - jak w MatchCandidatesFound
    protected function item(Animal $query, array $row, MatchSearch $search): array
    {
        $query->loadMissing(['species', 'breed', 'city', 'voivodeship']);
        $photo = $query->photos()->whereNull('sighting_id')->orderByDesc('is_main')->orderBy('id')->first();

        return [
            'title'          => $query->generated_title,
            'url'            => URL::signedRoute('matches.owner-click', ['matchSearch' => $search->id, 'position' => $row['position']]),
            'photo_url'      => $photo ? asset('storage/'.$photo->path) : null,
            'photo_position' => $photo?->objectPosition() ?? '50% 50%',
            'level'          => $row['level'],
            'location'       => $query->location_text,
            'date'           => $query->date_event?->format('d.m.Y'),
            'distance_km'    => (float) $row['distance_km'],
        ];
    }

    // ** Wypisanie (podpisany link, bez wygasania) i strona ogłoszenia autora (token edycji),
    // gdzie może oznaczyć zwierzaka jako odnalezionego
    protected function links(Animal $owner): array
    {
        return [
            'opt_out' => URL::signedRoute('matches.opt-out', ['animal' => $owner->id]),
            'manage'  => route('animals.edit', ['animal' => $owner, 'token' => $owner->edit_token]),
        ];
    }
}
