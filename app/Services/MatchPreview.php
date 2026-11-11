<?php

namespace App\Services;

use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchEvent;
use App\Models\MatchRequest;
use App\Models\MatchSearch;

// ---------------------------
// Dane do okna "Podobne (AI)" w panelu (podgląd zgłoszenia i ogłoszenia):
// - wyniki "na żywo" - to samo wyszukiwanie co przy mailu, bez zapisu i bez maila;
//   przy oczekującym zgłoszeniu = "tak wyglądałby mail, gdybyś teraz zatwierdził",
// - historia - co faktycznie poszło w mailu do autora (logi match_searches) i kliknięcia.
// ---------------------------

class MatchPreview
{
    public function __construct(private PhotoMatchService $matcher) {}

    public function build(Animal|AnimalEdit $record): array
    {
        return [
            'is_pending' => $record instanceof AnimalEdit && $record->mod_status === 'pending',
            'live' => $this->live($record),
            'request' => $this->request($record),
            'history' => $this->history($record),
        ];
    }

    // ---------------------------
    // Wyszukiwanie na żywo
    // ---------------------------
    protected function live(Animal|AnimalEdit $record): array
    {
        try {
            $out = $this->matcher->search($record);
        } catch (\Throwable $e) {
            report($e);

            return ['status' => 'error', 'error' => $e->getMessage(), 'rows' => []];
        }

        return [
            'status' => $out['status'],
            'params' => $out['params'],
            'n_candidates' => $out['n_candidates'],
            'n_compared' => $out['n_compared'],
            'n_pending_embedding' => $out['n_pending_embedding'],
            'rows' => $this->rows($out['results']),
        ];
    }

    // ** Wyniki z danymi ogłoszeń do wyświetlenia (miniaturka, tytuł, link do panelu)
    protected function rows(array $results): array
    {
        $animals = Animal::with(['photos' => fn ($q) => $q->whereNull('sighting_id')->orderByDesc('is_main')->orderBy('id')])
            ->findMany(array_column($results, 'listing_id'))
            ->keyBy('id');

        return collect($results)
            ->filter(fn ($r) => isset($animals[$r['listing_id']]))
            ->map(fn ($r) => $r + $this->animalCard($animals[$r['listing_id']]))
            ->values()
            ->all();
    }

    protected function animalCard(Animal $animal): array
    {
        $photo = $animal->photos->first();

        return [
            'title' => $animal->generated_title,
            'location' => $animal->location_text,
            'status' => $animal->status,
            'mod_status' => $animal->mod_status,
            'photo_url' => $photo ? asset('storage/'.$photo->path) : null,
            'url' => AnimalResource::getUrl('view', ['record' => $animal->id]),
        ];
    }

    // ---------------------------
    // Zlecenie maila (match_requests) - tylko dla zgłoszeń; dla ogłoszenia przez log wyszukiwania
    // ---------------------------
    protected function request(Animal|AnimalEdit $record): ?MatchRequest
    {
        if ($record instanceof AnimalEdit) {
            return MatchRequest::where('animal_edit_id', $record->id)->first();
        }

        $requestId = MatchSearch::where('query_animal_id', $record->id)
            ->whereNotNull('match_request_id')->latest('id')->value('match_request_id');

        return $requestId ? MatchRequest::find($requestId) : null;
    }

    // ---------------------------
    // Historia: zapisane wyszukiwania (mail do autora) z pokazanymi pozycjami i kliknięciami
    // ---------------------------
    protected function history(Animal|AnimalEdit $record): array
    {
        $searches = MatchSearch::query()
            ->where($record instanceof AnimalEdit ? 'query_animal_edit_id' : 'query_animal_id', $record->id)
            ->with(['results' => fn ($q) => $q->where('shown', true)->orderBy('position')])
            ->latest('id')
            ->limit(5)
            ->get();

        if ($searches->isEmpty()) {
            return [];
        }

        $animals = Animal::with(['photos' => fn ($q) => $q->whereNull('sighting_id')->orderByDesc('is_main')->orderBy('id')])
            ->findMany($searches->flatMap(fn ($s) => $s->results->pluck('animal_id'))->unique())
            ->keyBy('id');

        // ** Kliknięte pozycje z maila (słaby sygnał - skanery poczty też klikają)
        $clicks = MatchEvent::whereIn('match_search_id', $searches->pluck('id'))
            ->where('event', 'click')
            ->get(['match_search_id', 'position'])
            ->groupBy('match_search_id')
            ->map(fn ($events) => $events->pluck('position')->countBy()->all());

        return $searches->map(fn (MatchSearch $s) => [
            'created_at' => $s->created_at,
            'channel' => $s->channel,
            'model_version' => $s->model_version,
            'n_shown' => $s->n_shown,
            'rows' => $s->results->map(fn ($r) => [
                'position' => $r->position,
                'score' => (float) $r->score,
                'level' => $r->level,
                'distance_km' => $r->distance_km,
                'clicks' => $clicks[$s->id][$r->position] ?? 0,
            ] + (isset($animals[$r->animal_id])
                ? $this->animalCard($animals[$r->animal_id])
                : ['title' => "Ogłoszenie #{$r->animal_id} (usunięte)", 'url' => null, 'photo_url' => null, 'location' => null]))
                ->all(),
        ])->all();
    }
}
