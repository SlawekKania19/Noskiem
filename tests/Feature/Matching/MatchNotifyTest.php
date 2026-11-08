<?php

namespace Tests\Feature\Matching;

use App\Mail\MatchCandidatesFound;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchRequest;
use App\Services\PhotoMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// ---------------------------
// Zlecenia "poszukaj podobnych i napisz" (confirmEmail -> match_requests -> match:notify
// -> mail MatchCandidatesFound). Wyszukiwanie podmienione atrapą — ranking i filtry
// sprawdza PhotoMatchSearchTest.
// ---------------------------

class MatchNotifyTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    /** @var array<int, Animal|AnimalEdit> zapytania, z którymi wywołano wyszukiwanie */
    private array $searched = [];

    private array $nextResult = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        Mail::fake();

        // ** Atrapa wyszukiwania — zapamiętuje zapytanie, zwraca przygotowany wynik
        $this->app->instance(PhotoMatchService::class, new class($this) extends PhotoMatchService {
            public function __construct(private $test) {}

            public function search(Animal|AnimalEdit $query): array
            {
                return $this->test->fakeSearch($query);
            }
        });
    }

    public function fakeSearch(Animal|AnimalEdit $query): array
    {
        $this->searched[] = $query;

        // ** Pełny format wyniku PhotoMatchService::search() (logger wymaga wszystkich pól)
        return $this->nextResult + ['status' => 'ok', 'results' => [], 'model_version' => $this->mv,
            'params' => ['filters' => ['radius_km' => 150]],
            'n_candidates' => 0, 'n_compared' => 0, 'n_pending_embedding' => 0];
    }

    private function matchRow(Animal $animal, string $level = 'strong', bool $shown = true): array
    {
        return ['listing_id' => $animal->id, 'score' => 0.8, 'bonus' => 0.0, 'level' => $level, 'shown' => $shown,
            'position' => $shown ? 1 : null, 'rank' => $shown ? 1 : 2, 'breed_match' => null, 'color_overlap' => null,
            'best_photo_index' => 0, 'distance_km' => 12.3];
    }

    // ** Zgłoszenie ze zleceniem; domyślnie od razu zatwierdzone (mail tylko po zatwierdzeniu)
    private function queuedEdit(array $attrs = [], bool $approve = true): AnimalEdit
    {
        $edit = $this->edit(attrs: $attrs);
        $this->photo(['animal_edit_id' => $edit->id]);
        MatchRequest::create(['animal_edit_id' => $edit->id]);

        if ($approve) {
            $this->approve($edit);
        }

        return $edit;
    }

    public function test_potwierdzenie_maila_tworzy_zlecenie_i_zapowiada_wyszukiwanie(): void
    {
        $edit = $this->edit(verified: false);
        $this->photo(['animal_edit_id' => $edit->id]);

        $this->get(route('animal-edits.confirm', ['animalEdit' => $edit, 'token' => $edit->edit_token]))
            ->assertRedirect()
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Po zatwierdzeniu poszukamy też podobnych'));

        $this->assertSame('queued', MatchRequest::where('animal_edit_id', $edit->id)->value('status'));

        // ** Ponowne kliknięcie linku nie dubluje zlecenia
        $this->get(route('animal-edits.confirm', ['animalEdit' => $edit, 'token' => $edit->edit_token]));
        $this->assertSame(1, MatchRequest::count());
    }

    public function test_bez_zdjec_lub_przy_wylaczonym_module_nie_obiecujemy_wyszukiwania(): void
    {
        $noPhotos = $this->edit(verified: false);
        $this->get(route('animal-edits.confirm', ['animalEdit' => $noPhotos, 'token' => $noPhotos->edit_token]))
            ->assertSessionHas('success', fn ($m) => ! str_contains($m, 'podobnych'));

        config(['noskiem_match.enabled' => false]);
        $disabled = $this->edit(verified: false);
        $this->photo(['animal_edit_id' => $disabled->id]);
        $this->get(route('animal-edits.confirm', ['animalEdit' => $disabled, 'token' => $disabled->edit_token]))
            ->assertSessionHas('success', fn ($m) => ! str_contains($m, 'podobnych'));

        $this->assertSame(0, MatchRequest::count());
    }

    public function test_mail_z_kandydatami_tylko_gdy_cos_przekracza_prog(): void
    {
        $edit = $this->queuedEdit();
        $cand = $this->animal('approved', ['status' => 'found', 'contact_email' => 'a@example.com']);
        $this->photo(['animal_id' => $cand->id]);
        $below = $this->animal('approved', ['status' => 'found', 'contact_email' => 'b@example.com']);

        $this->nextResult = ['results' => [$this->matchRow($cand), $this->matchRow($below, 'below', false)]];
        $this->artisan('match:notify')->assertSuccessful();

        Mail::assertSent(MatchCandidatesFound::class, function (MatchCandidatesFound $mail) use ($edit, $cand) {
            return $mail->hasTo($edit->contact_email)
                && count($mail->items) === 1                       // poniżej progu nie trafia do maila
                && str_contains($mail->items[0]['url'], '/dopasowania/')
                && str_contains($mail->items[0]['url'], 'signature=')
                && $mail->items[0]['photo_url'] !== null;
        });
        $req = MatchRequest::first();
        $this->assertSame(['notified', 1], [$req->status, $req->results_shown]);

        // ** Kolejne uruchomienie nie wysyła drugi raz
        $this->artisan('match:notify');
        Mail::assertSentCount(1);
    }

    public function test_brak_dopasowan_bez_maila_a_brak_wektorow_czeka(): void
    {
        $this->queuedEdit();

        $this->nextResult = ['status' => 'pending'];
        $this->artisan('match:notify');
        $this->assertSame('queued', MatchRequest::first()->status);

        $this->nextResult = ['status' => 'ok', 'results' => []];
        $this->artisan('match:notify');
        $this->assertSame('no_matches', MatchRequest::first()->status);
        Mail::assertNothingSent();
    }

    public function test_mail_dopiero_po_zatwierdzeniu_a_odrzucone_pomijamy(): void
    {
        $edit = $this->queuedEdit(approve: false);
        $rejected = $this->queuedEdit(approve: false);
        $cand = $this->animal('approved', ['status' => 'found', 'contact_email' => 'a@example.com']);
        $this->nextResult = ['results' => [$this->matchRow($cand)]];

        // ** Czeka na moderację — bez wyszukiwania i bez maila, nawet gdy wektory są gotowe
        $this->artisan('match:notify');
        $this->assertSame([], $this->searched);
        $this->assertSame(2, MatchRequest::where('status', 'queued')->count());
        Mail::assertNothingSent();

        $rejected->update(['mod_status' => 'rejected']);
        $animal = $this->approve($edit);
        $this->artisan('match:notify');

        // ** Szukamy dla zatwierdzonego ogłoszenia (nie zgłoszenia), odrzucone bez maila
        $this->assertCount(1, $this->searched);
        $this->assertInstanceOf(Animal::class, $this->searched[0]);
        $this->assertSame($animal->id, $this->searched[0]->id);
        Mail::assertSent(MatchCandidatesFound::class, fn ($mail) => $mail->hasTo($edit->contact_email));
        $this->assertSame('skipped', MatchRequest::where('animal_edit_id', $rejected->id)->value('status'));
    }

    public function test_oczekujace_nie_blokuja_partii_i_wygasaja_po_terminie(): void
    {
        config(['noskiem_match.notify.batch' => 2]);

        // ** Więcej oczekujących niż mieści się w partii — zatwierdzone i tak przechodzi
        $this->queuedEdit(approve: false);
        $this->queuedEdit(approve: false);
        $this->queuedEdit(approve: false);
        $approved = $this->queuedEdit();

        $this->artisan('match:notify');
        $this->assertSame('no_matches', MatchRequest::where('animal_edit_id', $approved->id)->value('status'));

        // ** Nikt nie zmoderował przez 30 dni → wygasa
        $this->travel(31)->days();
        $this->artisan('match:notify');
        $this->artisan('match:notify');
        $this->assertSame(3, MatchRequest::where('status', 'expired')->count());
        $this->assertCount(1, $this->searched);
    }

    public function test_wygasanie_ponawianie_i_pauza(): void
    {
        $fresh = $this->queuedEdit();
        // ** Termin liczony od zatwierdzenia, nie od zgłoszenia
        $old = $this->queuedEdit(approve: false);
        $this->approve($old, now()->subHours(73));
        // ** Zgłoszenie sprzed 5 dni, zatwierdzone dopiero teraz (wolna moderacja) — nie wygasa
        $slowModeration = $this->queuedEdit(approve: false);
        MatchRequest::where('animal_edit_id', $slowModeration->id)->update(['created_at' => now()->subDays(5)]);
        $this->approve($slowModeration);

        config(['noskiem_match.queue.paused' => true]);
        $this->artisan('match:notify');
        $this->assertSame(3, MatchRequest::where('status', 'queued')->count());   // pauza: nic

        config(['noskiem_match.queue.paused' => false]);
        $this->nextResult = ['status' => 'pending'];                              // wektory jeszcze liczone
        $this->artisan('match:notify');
        $this->assertSame('expired', MatchRequest::where('animal_edit_id', $old->id)->value('status'));
        $this->assertSame('queued', MatchRequest::where('animal_edit_id', $slowModeration->id)->value('status'));
        MatchRequest::where('animal_edit_id', $slowModeration->id)->delete();

        // ** Błąd wyszukiwania (wyjątek) — 3 próby, potem failed
        $this->app->instance(PhotoMatchService::class, new class extends PhotoMatchService {
            public function __construct() {}

            public function search(Animal|AnimalEdit $query): array
            {
                throw new \RuntimeException('python padł');
            }
        });

        $this->artisan('match:notify');
        $this->artisan('match:notify');
        $this->artisan('match:notify');
        $req = MatchRequest::where('animal_edit_id', $fresh->id)->first();
        $this->assertSame(['failed', 3, 'python padł'], [$req->status, $req->attempts, $req->last_error]);
    }

    public function test_mail_potwierdzajacy_zapowiada_wyszukiwanie_tylko_ze_zdjeciami(): void
    {
        $withPhoto = $this->edit(verified: false);
        $this->photo(['animal_edit_id' => $withPhoto->id]);
        $without = $this->edit(verified: false);

        $this->assertStringContainsString('porównamy zdjęcia', (new \App\Mail\AnimalSubmissionReceived($withPhoto))->render());
        $this->assertStringNotContainsString('porównamy zdjęcia', (new \App\Mail\AnimalSubmissionReceived($without))->render());
    }

    public function test_tresc_maila(): void
    {
        $edit = $this->edit();
        $html = (new MatchCandidatesFound($edit, [[
            'title' => 'Znaleziony pies — Katowice', 'url' => 'https://noskiem.test/animals/5',
            'photo_url' => 'https://noskiem.test/storage/photos/a.jpg', 'level' => 'strong',
            'location' => 'Park', 'date' => '05.11.2026', 'distance_km' => 12.3,
        ]], ['filters' => ['radius_km' => 150]]))->render();

        $this->assertStringContainsString('Bardzo podobne', $html);
        $this->assertStringContainsString('znalezionych', $html);           // zgłoszenie "lost"
        $this->assertStringContainsString('w promieniu ok. 150 km).', $html);
        $this->assertStringContainsString('ok. 12 km od Ciebie', $html);
        $this->assertStringContainsString('może się mylić', $html);
    }
}
