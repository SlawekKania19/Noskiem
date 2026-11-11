<?php

namespace Tests\Feature\Matching;

use App\Services\PhotoMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

// ---------------------------
// Wyszukiwanie podobnych ogłoszeń (PhotoMatchService): statusy, twarde filtry puli,
// dane przekazywane do Pythona. Ranking sprawdzają testy Pythona
// (noskiem_match/tests) oraz test z prawdziwym cli.py na końcu.
// ---------------------------

class PhotoMatchSearchTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    private ?array $sent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
    }

    // ** Atrapa Pythona: zapamiętuje żądanie (i rozmiar pliku wektorów), zwraca pusty ranking
    private function fakePython(): void
    {
        Process::fake(['*' => function (PendingProcess $p) {
            $this->sent = json_decode($p->input, true);
            $this->sent['_file_bytes'] = filesize($this->sent['vectors_file']);

            return Process::result(json_encode(['status' => 'ok', 'model_version' => $this->mv, 'n_compared' => 0, 'results' => []]));
        }]);
    }

    private function sentIds(): array
    {
        return collect($this->sent['candidates'] ?? [])->pluck('listing_id')->sort()->values()->all();
    }

    public function test_statusy_gdy_nie_ma_czego_porownac(): void
    {
        $svc = app(PhotoMatchService::class);
        Process::fake();

        $this->assertSame('no_photos', $svc->search($this->edit())['status']);

        $edit = $this->edit();
        $this->photoWithVector(['animal_edit_id' => $edit->id], [1, 0, 0, 0]);
        $this->photo(['animal_edit_id' => $edit->id]);              // drugie jeszcze bez wektora
        $this->assertSame('pending', $svc->search($edit)['status']);

        $broken = $this->edit();
        $this->photoWithVector(['animal_edit_id' => $broken->id], null);
        $this->assertSame('no_embedding', $svc->search($broken)['status']);

        Process::assertNothingRan();
    }

    public function test_twarde_filtry_puli_kandydatow(): void
    {
        $other = $this->animal('approved', ['species_id' => \App\Models\Species::forceCreate(['name_pl' => 'Kot', 'name_en' => 'Cat'])->id, 'status' => 'found']);
        $own = $this->animal('approved', ['status' => 'found']);
        $query = $this->edit(attrs: ['animal_id' => $own->id]);      // edycja istniejącego
        $this->photoWithVector(['animal_edit_id' => $query->id], [1, 0, 0, 0]);

        $ok = $this->animal('approved', ['status' => 'found', 'contact_email' => 'inny@example.com']);
        $sameStatus = $this->animal('approved', ['status' => 'lost', 'contact_email' => 'inny@example.com']);
        $pendingMod = $this->animal('pending', ['status' => 'found', 'contact_email' => 'inny@example.com']);
        $sameEmail = $this->animal('approved', ['status' => 'found', 'contact_email' => 'JAN@example.com']);
        // Gliwice ~25 km - w promieniu; Gdańsk ~480 km - poza
        $near = $this->animal('approved', ['status' => 'found', 'contact_email' => 'b@example.com', 'latitude' => 50.29, 'longitude' => 18.67]);
        $far = $this->animal('approved', ['status' => 'found', 'contact_email' => 'c@example.com', 'latitude' => 54.35, 'longitude' => 18.65]);
        $noVector = $this->animal('approved', ['status' => 'found', 'contact_email' => 'd@example.com']);
        $this->photo(['animal_id' => $noVector->id]);

        foreach ([$other, $own, $ok, $sameStatus, $pendingMod, $sameEmail, $near, $far] as $a) {
            $this->photoWithVector(['animal_id' => $a->id], [0.9, 0.1, 0, 0]);
        }
        $this->photoWithVector(['animal_id' => $ok->id], [0, 1, 0, 0]);   // 2. zdjęcie kandydata

        $this->fakePython();
        $out = app(PhotoMatchService::class)->search($query);

        $this->assertSame('ok', $out['status']);
        $this->assertSame([$ok->id, $near->id], $this->sentIds());
        $this->assertSame(3, $out['n_candidates']);                  // ok, near, noVector
        $this->assertSame(1, $out['n_pending_embedding']);           // noVector
        // ** Plik: 1 zdjęcie zapytania + 2 (ok) + 1 (near) wierszy × 4 floaty × 4 B
        $this->assertSame(4 * 4 * 4, $this->sent['_file_bytes']);
        $this->assertSame([0, 1], $this->sent['query_rows']);
        $okSent = collect($this->sent['candidates'])->firstWhere('listing_id', $ok->id);
        $this->assertSame([1, 2], $okSent['rows']);
        $this->assertFileDoesNotExist($this->sent['vectors_file']);  // plik tymczasowy sprzątnięty

        // ** Wyłączone filtry - pula rośnie (poza własnym ogłoszeniem i niezatwierdzonym)
        config(['noskiem_match.search.filters' => [
            'opposite_status' => false, 'same_species' => false, 'radius_km' => 0,
            'date_window_days' => 0, 'exclude_same_email' => false,
        ]]);
        app(PhotoMatchService::class)->search($query);
        $this->assertSame(
            collect([$other, $ok, $sameStatus, $sameEmail, $near, $far])->pluck('id')->sort()->values()->all(),
            $this->sentIds(),
        );
    }

    public function test_okno_dat_domyslnie_wylaczone_i_dziala_po_wlaczeniu(): void
    {
        $query = $this->edit();
        $this->photoWithVector(['animal_edit_id' => $query->id], [1, 0, 0, 0]);
        $recent = $this->animal('approved', ['status' => 'found', 'contact_email' => 'a@example.com', 'date_event' => '2026-11-05']);
        $old = $this->animal('approved', ['status' => 'found', 'contact_email' => 'b@example.com', 'date_event' => '2025-01-01']);
        $this->photoWithVector(['animal_id' => $recent->id], [1, 0, 0, 0]);
        $this->photoWithVector(['animal_id' => $old->id], [1, 0, 0, 0]);
        $this->fakePython();

        app(PhotoMatchService::class)->search($query);
        $this->assertSame([$recent->id, $old->id], $this->sentIds());

        config(['noskiem_match.search.filters.date_window_days' => 30]);
        app(PhotoMatchService::class)->search($query);
        $this->assertSame([$recent->id], $this->sentIds());
    }

    public function test_rasa_nieznana_i_kolory_jako_metadane(): void
    {
        $unknown = $this->breed('Rasa nieznana');
        $beagle = $this->breed('Beagle');
        $query = $this->edit(attrs: ['breed_id' => $unknown->id]);
        $this->photoWithVector(['animal_edit_id' => $query->id], [1, 0, 0, 0]);
        $color = \App\Models\Color::forceCreate(['name' => 'czarny']);
        $query->colors()->sync([$color->id]);

        $cand = $this->animal('approved', ['status' => 'found', 'contact_email' => 'a@example.com', 'breed_id' => $beagle->id]);
        $cand->colors()->sync([$color->id]);
        $this->photoWithVector(['animal_id' => $cand->id], [1, 0, 0, 0]);
        $this->fakePython();

        $out = app(PhotoMatchService::class)->search($query);

        $this->assertNull($this->sent['query']['breed_id']);         // "Rasa nieznana" = brak danych
        $this->assertSame([$color->id], $this->sent['query']['color_ids']);
        $this->assertSame($beagle->id, $this->sent['candidates'][0]['breed_id']);
        $this->assertSame([$color->id], $this->sent['candidates'][0]['color_ids']);
        // ** Parametry w wyniku = dokładnie to, co poszło do Pythona (do logu)
        $this->assertEquals($out['params'], $this->sent['params']);
        $this->assertEquals(0.60, $out['params']['threshold_possible']);
    }

    // ** End-to-end z prawdziwym cli.py search (wymaga lokalnego Pythona z numpy)
    public function test_prawdziwy_python_ranking_i_progi(): void
    {
        $python = env('MATCH_TEST_PYTHON', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
        if (! Process::run([$python, '-c', 'import numpy'])->successful()) {
            $this->markTestSkipped("Brak Pythona z numpy ({$python}) - ustaw MATCH_TEST_PYTHON");
        }
        config(['noskiem_match.python' => $python]);

        $query = $this->edit();
        $this->photoWithVector(['animal_edit_id' => $query->id], [1, 0, 0, 0]);
        $this->photoWithVector(['animal_edit_id' => $query->id], [0, 0, 1, 0]);

        $mk = fn (string $email, array ...$vecs) => tap(
            $this->animal('approved', ['status' => 'found', 'contact_email' => $email]),
            fn ($a) => array_map(fn ($v) => $this->photoWithVector(['animal_id' => $a->id], $v), $vecs),
        );
        // cos z 1. zdjęciem zapytania: same ≈ 0.95 (strong), maybe ≈ 0.65 (possible), other ≈ 0.10
        $same = $mk('a@example.com', [0, 1, 0, 0], [0.95, 0.312, 0, 0]);   // dobre zdjęcie jako 2.
        $maybe = $mk('b@example.com', [0.65, 0.76, 0, 0]);
        $other = $mk('c@example.com', [0.1, 0.995, 0, 0]);

        $out = app(PhotoMatchService::class)->search($query);
        $by = collect($out['results'])->keyBy('listing_id');

        $this->assertSame('ok', $out['status']);
        $this->assertSame(3, $out['n_compared']);
        $this->assertSame([1, 'strong', 1], [$by[$same->id]['position'], $by[$same->id]['level'], $by[$same->id]['best_photo_index']]);
        $this->assertSame([2, 'possible'], [$by[$maybe->id]['position'], $by[$maybe->id]['level']]);
        $this->assertSame([false, 'below'], [$by[$other->id]['shown'], $by[$other->id]['level']]);  // w logu, nie na liście
        $this->assertEqualsWithDelta(0.0, $by[$same->id]['distance_km'], 0.01);
    }
}
