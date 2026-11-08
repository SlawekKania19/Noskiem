<?php

namespace Tests\Feature\Matching;

use App\Mail\MatchCandidatesFound;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchEvent;
use App\Models\MatchRequest;
use App\Models\MatchSearch;
use App\Services\PhotoMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

// ---------------------------
// Logi wyszukiwań (match_searches/match_search_results) i zdarzenia kliknięć
// (match_events, MatchClickController). Wyszukiwanie podmienione atrapą.
// ---------------------------

class MatchLoggingTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    private array $nextResult = [];

    // ** Ogłoszenie powstałe z zatwierdzenia zgłoszenia w scenario()
    private ?Animal $listing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        Mail::fake();

        $this->app->instance(PhotoMatchService::class, new class($this) extends PhotoMatchService {
            public function __construct(private $test) {}

            public function search(Animal|AnimalEdit $query): array
            {
                return $this->test->fakeResult();
            }
        });
    }

    public function fakeResult(): array
    {
        return $this->nextResult;
    }

    private function row(Animal $a, int $rank, float $score, string $level, ?int $position): array
    {
        return ['listing_id' => $a->id, 'score' => $score, 'bonus' => 0.02, 'rank' => $rank, 'level' => $level,
            'shown' => $position !== null, 'position' => $position, 'breed_match' => true,
            'color_overlap' => 0.5, 'best_photo_index' => 0, 'distance_km' => 3.2];
    }

    private function ok(array $results): array
    {
        return ['status' => 'ok', 'model_version' => $this->mv, 'results' => $results,
            'params' => ['threshold_possible' => 0.6, 'threshold_strong' => 0.75, 'top_n' => 10, 'filters' => ['radius_km' => 150]],
            'n_candidates' => 5, 'n_compared' => 4, 'n_pending_embedding' => 1];
    }

    // ** Zatwierdzone zgłoszenie z zleceniem (mail tylko po zatwierdzeniu),
    // dwóch kandydatów nad progiem i jeden poniżej
    private function scenario(): array
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        $req = MatchRequest::create(['animal_edit_id' => $edit->id]);
        $this->listing = $this->approve($edit);
        $a = $this->animal('approved', ['status' => 'found', 'contact_email' => 'a@example.com']);
        $b = $this->animal('approved', ['status' => 'found', 'contact_email' => 'b@example.com']);
        $c = $this->animal('approved', ['status' => 'found', 'contact_email' => 'c@example.com']);
        $this->nextResult = $this->ok([
            $this->row($a, 1, 0.81, 'strong', 1),
            $this->row($c, 2, 0.58, 'below', null),   // poniżej progu, ale wyżej w rankingu niż b
            $this->row($b, 3, 0.62, 'possible', 2),
        ]);

        return [$edit, $req, $a, $b, $c];
    }

    public function test_log_wyszukiwania_zawiera_tez_kandydatow_ponizej_progu(): void
    {
        [$edit, $req, $a, $b, $c] = $this->scenario();

        $this->artisan('match:notify')->assertSuccessful();

        $search = MatchSearch::sole();
        $this->assertSame([$req->id, $edit->id, $this->listing->id, $this->mv, 'notify', 2, 5, 4, 1], [
            $search->match_request_id, $search->query_animal_edit_id, $search->query_animal_id,
            $search->model_version, $search->channel, $search->n_shown,
            $search->n_candidates, $search->n_compared, $search->n_pending_embedding,
        ]);
        $this->assertEquals(0.6, $search->params['threshold_possible']);

        $rows = $search->results()->orderBy('rank')->get();
        $this->assertSame([$a->id, $c->id, $b->id], $rows->pluck('animal_id')->all());
        $this->assertSame([true, false, true], $rows->pluck('shown')->all());
        $this->assertSame([1, null, 2], $rows->pluck('position')->all());
        $this->assertSame('below', $rows[1]->level);
        $this->assertEqualsWithDelta(0.58, $rows[1]->score, 1e-6);
    }

    public function test_bez_wynikow_nad_progiem_tez_logujemy_a_pending_nie(): void
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        MatchRequest::create(['animal_edit_id' => $edit->id]);
        $this->approve($edit);
        $x =$this->animal('approved', ['status' => 'found', 'contact_email' => 'x@example.com']);

        $this->nextResult = ['status' => 'pending'];
        $this->artisan('match:notify');
        $this->assertSame(0, MatchSearch::count());

        $this->nextResult = $this->ok([$this->row($x, 1, 0.4, 'below', null)]);
        $this->artisan('match:notify');
        $search = MatchSearch::sole();
        $this->assertSame(0, $search->n_shown);
        $this->assertSame(1, $search->results()->count());
        Mail::assertNothingSent();
    }

    public function test_klik_z_maila_zapisuje_zdarzenie_z_pozycja_i_przekierowuje(): void
    {
        [, , , $b] = $this->scenario();
        $this->artisan('match:notify');

        $url = null;
        Mail::assertSent(MatchCandidatesFound::class, function ($mail) use (&$url) {
            $url = $mail->items[1]['url'];                      // druga pozycja na liście

            return true;
        });

        $this->withHeader('User-Agent', 'Mozilla/5.0 test')->get($url)->assertRedirect(route('animals.show', $b));

        $event = MatchEvent::sole();
        $this->assertSame(['click', 'email', $b->id, 2, $this->mv, 'Mozilla/5.0 test'], [
            $event->event, $event->source, $event->animal_id, $event->position, $event->model_version, $event->user_agent,
        ]);
        $this->assertSame(MatchSearch::sole()->id, $event->match_search_id);
    }

    public function test_klik_bez_podpisu_niepokazana_pozycja_i_nieaktywne_ogloszenie(): void
    {
        [, , $a] = $this->scenario();
        $this->artisan('match:notify');
        $search = MatchSearch::sole();

        // ** Bez podpisu / z podmienioną pozycją — 403, nic nie zapisujemy
        $this->get(route('matches.click', ['matchSearch' => $search->id, 'position' => 1]))->assertForbidden();
        $signed = URL::signedRoute('matches.click', ['matchSearch' => $search->id, 'position' => 1]);
        $this->get(str_replace('/klik/1', '/klik/2', $signed))->assertForbidden();

        // ** Poprawnie podpisana, ale pozycja spoza listy — 404
        $this->get(URL::signedRoute('matches.click', ['matchSearch' => $search->id, 'position' => 7]))->assertNotFound();
        $this->assertSame(0, MatchEvent::count());

        // ** Ogłoszenie odnalezione od czasu maila — klik zapisany, przekierowanie na listę
        $a->update(['mod_status' => 'resolved']);
        $this->get($signed)->assertRedirect(route('animals.index'));
        $this->assertSame(1, MatchEvent::count());
    }

    public function test_log_przetrwa_usuniecie_ogloszenia(): void
    {
        [, , $a] = $this->scenario();
        $this->artisan('match:notify');

        $a->delete();

        $this->assertSame(3, MatchSearch::sole()->results()->count());
    }
}
