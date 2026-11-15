<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchExclusion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

// ---------------------------
// Propozycje AI odrzucone przez moderatora (przycisk "Nie pokazuj" w oknie "Podobne (AI)").
// Dodatkowy filtr na niedorzeczne dopasowania z wysokim wynikiem - zanim pójdzie mail
// i zrobi autorowi nadzieję.
// - wykluczenie działa w obie strony: A nie pasuje do B => B nie pasuje do A
//   (więc nie idzie też mail do autora starego ogłoszenia - MatchOwnerNotifier)
// - wykluczenie zapisane na oczekującym zgłoszeniu przechodzi na ogłoszenie po
//   zatwierdzeniu (carryOver z ModerationLog::created)
// - apply() nie usuwa kandydata z wyników, tylko oznacza go "excluded" i zdejmuje
//   z listy pokazanych - w panelu widać, co odrzucono, a w logu zostaje ślad do kalibracji
// ---------------------------

class MatchExclusions
{
    // ---------------------------
    // Odrzuceni kandydaci dla zapytania: [candidate_animal_id => MatchExclusion]
    // ---------------------------
    public function for(Animal|AnimalEdit $query): Collection
    {
        [$editId, $animalId] = $this->keys($query);

        if ($editId === null && $animalId === null) {
            return collect();
        }

        $rows = MatchExclusion::with('user:id,name')
            ->where(function (Builder $q) use ($editId, $animalId) {
                $q->when($editId, fn (Builder $q) => $q->orWhere('animal_edit_id', $editId))
                    ->when($animalId, fn (Builder $q) => $q->orWhere('animal_id', $animalId)
                        // ** Druga strona pary - ktoś odrzucił TO ogłoszenie jako kandydata innego
                        ->orWhere(fn (Builder $q) => $q->where('candidate_animal_id', $animalId)->whereNotNull('animal_id')));
            })
            ->get();

        return $rows
            ->mapWithKeys(fn (MatchExclusion $e) => [
                ($animalId !== null && $e->candidate_animal_id === $animalId ? $e->animal_id : $e->candidate_animal_id) => $e,
            ]);
    }

    // ---------------------------
    // Oznacza odrzuconych w wyniku PhotoMatchService::search() i przelicza listę pokazanych.
    // Logika jak w noskiem_match/search.py: pokazane = ponad progiem, najwyżej top_n,
    // pozycje 1..n bez dziur - więc w miejsce odrzuconego może wejść kolejny ponad progiem
    // ---------------------------
    public function apply(Animal|AnimalEdit $query, array $out): array
    {
        if (($out['status'] ?? null) !== 'ok' || $out['results'] === []) {
            return $out;
        }

        $excluded = $this->for($query);
        $topN = (int) ($out['params']['top_n'] ?? config('noskiem_match.search.top_n'));
        $position = 0;

        foreach ($out['results'] as &$r) {
            $r['excluded'] = $excluded->has($r['listing_id']);
            $r['excluded_by'] = $excluded->get($r['listing_id'])?->user?->name;

            if (! $r['excluded'] && $r['level'] !== 'below' && $position < $topN) {
                $r['shown'] = true;
                $r['position'] = ++$position;
            } else {
                $r['shown'] = false;
                $r['position'] = null;
            }
        }
        unset($r);

        return $out;
    }

    // ---------------------------
    // Przełącznik z panelu - zwraca true, gdy kandydat jest teraz odrzucony
    // ---------------------------
    public function toggle(Animal|AnimalEdit $query, int $candidateId, ?int $userId): bool
    {
        [$editId, $animalId] = $this->keys($query);

        $existing = $this->for($query)->get($candidateId);

        if ($existing) {
            // ** Cofnięcie - usuwamy wszystkie zapisy tej pary (obie strony, zgłoszenie i ogłoszenie)
            MatchExclusion::query()
                ->where(function (Builder $q) use ($editId, $animalId, $candidateId) {
                    $q->when($editId, fn (Builder $q) => $q->orWhere(fn (Builder $q) => $q->where('animal_edit_id', $editId)->where('candidate_animal_id', $candidateId)))
                        ->when($animalId, fn (Builder $q) => $q
                            ->orWhere(fn (Builder $q) => $q->where('animal_id', $animalId)->where('candidate_animal_id', $candidateId))
                            ->orWhere(fn (Builder $q) => $q->where('animal_id', $candidateId)->where('candidate_animal_id', $animalId)));
                })
                ->delete();

            return false;
        }

        MatchExclusion::create([
            'animal_edit_id' => $editId,
            'animal_id' => $animalId,
            'candidate_animal_id' => $candidateId,
            'user_id' => $userId,
        ]);

        return true;
    }

    // ** Po zatwierdzeniu zgłoszenia - wykluczenia przechodzą na nowe ogłoszenie
    public function carryOver(int $editId, int $animalId): void
    {
        MatchExclusion::where('animal_edit_id', $editId)->whereNull('animal_id')->update(['animal_id' => $animalId]);
    }

    // ** [animal_edit_id, animal_id] zapytania. Edycja istniejącego ogłoszenia ma oba
    protected function keys(Animal|AnimalEdit $query): array
    {
        return $query instanceof AnimalEdit
            ? [$query->id, $query->animal_id]
            : [null, $query->id];
    }
}
