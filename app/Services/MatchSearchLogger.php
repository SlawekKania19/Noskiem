<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchRequest;
use App\Models\MatchSearch;
use App\Models\MatchSearchResult;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Zapis wyszukiwania podobnych zwierząt do logów (match_searches + match_search_results).
// Logujemy WSZYSTKIE wyniki zwrócone przez Pythona: pokazane oraz top log_floor_n
// poniżej progu - do późniejszego sprawdzenia, czy próg nie ukrywał prawdziwych par.
// ---------------------------

class MatchSearchLogger
{
    /**
     * @param  array  $out  wynik PhotoMatchService::search() ze statusem "ok"
     */
    public function record(array $out, Animal|AnimalEdit $query, string $channel, ?MatchRequest $request = null): MatchSearch
    {
        return DB::transaction(function () use ($out, $query, $channel, $request) {
            $search = MatchSearch::create([
                'match_request_id'     => $request?->id,
                'query_animal_edit_id' => $query instanceof AnimalEdit ? $query->id : $request?->animal_edit_id,
                'query_animal_id'      => $query instanceof Animal ? $query->id : $query->animal_id,
                'model_version'        => $out['model_version'],
                'params'               => $out['params'],
                'n_candidates'         => $out['n_candidates'],
                'n_compared'           => $out['n_compared'],
                'n_pending_embedding'  => $out['n_pending_embedding'],
                'n_shown'              => count(array_filter($out['results'], fn ($r) => $r['shown'])),
                'channel'              => $channel,
            ]);

            // ** Jednym zapytaniem na partię - przy log_floor_n = 50 to i tak mało wierszy
            foreach (array_chunk($out['results'], 200) as $chunk) {
                MatchSearchResult::insert(array_map(fn (array $r) => [
                    'match_search_id'  => $search->id,
                    'animal_id'        => $r['listing_id'],
                    'score'            => $r['score'],
                    'bonus'            => $r['bonus'],
                    'rank'             => $r['rank'],
                    'level'            => $r['level'],
                    'shown'            => $r['shown'],
                    'position'         => $r['position'],
                    'breed_match'      => $r['breed_match'],
                    'color_overlap'    => $r['color_overlap'],
                    'distance_km'      => $r['distance_km'] ?? null,
                    'best_photo_index' => $r['best_photo_index'] ?? null,
                ], $chunk));
            }

            return $search;
        });
    }
}
