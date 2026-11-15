<?php

namespace Tests\Feature\Matching;

use App\Filament\Resources\AnimalEditResource\Pages\ViewAnimalEdit;
use App\Mail\MatchCandidatesFound;
use App\Mail\NewSimilarListing;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchExclusion;
use App\Models\MatchRequest;
use App\Models\MatchSearchResult;
use App\Models\User;
use App\Services\MatchExclusions;
use App\Services\PhotoMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

// ---------------------------
// Odrzucanie propozycji AI przez moderatora ("Nie pokazuj" w oknie "Podobne (AI)"):
// odrzucony kandydat nie trafia do maila autora ani do autora kandydata, przechodzi
// ze zgłoszenia na ogłoszenie i zostaje w logu jako excluded. Wyszukiwanie - atrapa.
// ---------------------------

class MatchExclusionTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    private array $results = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        config(['noskiem_match.notify.owners_enabled' => true]);
        Mail::fake();

        $this->app->instance(PhotoMatchService::class, new class($this) extends PhotoMatchService {
            public function __construct(private $test) {}

            public function search(Animal|AnimalEdit $query): array
            {
                return $this->test->fakeSearch();
            }
        });
    }

    public function fakeSearch(): array
    {
        return ['status' => 'ok', 'results' => $this->results, 'model_version' => $this->mv,
            'params' => ['top_n' => 2, 'threshold_possible' => 0.6, 'threshold_strong' => 0.75, 'filters' => ['radius_km' => 150]],
            'n_candidates' => count($this->results), 'n_compared' => count($this->results), 'n_pending_embedding' => 0];
    }

    // ** Wiersz wyniku jak z Pythona: pokazane = ponad progiem, najwyżej top_n
    private function row(Animal $a, int $rank, string $level, ?int $position): array
    {
        return ['listing_id' => $a->id, 'score' => 0.9 - $rank / 10, 'bonus' => 0.0, 'rank' => $rank, 'level' => $level,
            'shown' => $position !== null, 'position' => $position, 'breed_match' => null, 'color_overlap' => null,
            'best_photo_index' => 0, 'distance_km' => 1.0];
    }

    private function candidate(string $email): Animal
    {
        $animal = $this->animal('approved', ['status' => 'found', 'contact_email' => $email]);
        $this->photo(['animal_id' => $animal->id]);

        return $animal;
    }

    public function test_odrzucony_kandydat_zwalnia_miejsce_kolejnemu_ponad_progiem(): void
    {
        $edit = $this->edit();
        [$a, $b, $c, $d] = [$this->candidate('a@x.pl'), $this->candidate('b@x.pl'), $this->candidate('c@x.pl'), $this->candidate('d@x.pl')];

        // ** top_n = 2: C jest ponad progiem, ale poza limitem; D poniżej progu
        $this->results = [$this->row($a, 1, 'strong', 1), $this->row($b, 2, 'possible', 2),
            $this->row($c, 3, 'possible', null), $this->row($d, 4, 'below', null)];

        $service = app(MatchExclusions::class);
        $service->toggle($edit, $a->id, null);

        $out = collect($service->apply($edit, $this->fakeSearch())['results'])->keyBy('listing_id');

        $this->assertTrue($out[$a->id]['excluded']);
        $this->assertFalse($out[$a->id]['shown']);
        $this->assertSame([true, 1], [$out[$b->id]['shown'], $out[$b->id]['position']]);
        $this->assertSame([true, 2], [$out[$c->id]['shown'], $out[$c->id]['position']]);
        $this->assertFalse($out[$d->id]['shown']);
    }

    public function test_moderator_odrzuca_przed_zatwierdzeniem_i_mail_idzie_bez_tej_pozycji(): void
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        MatchRequest::create(['animal_edit_id' => $edit->id]);
        $absurd = $this->candidate('absurd@x.pl');
        $good = $this->candidate('good@x.pl');
        $this->results = [$this->row($absurd, 1, 'strong', 1), $this->row($good, 2, 'possible', 2)];

        $moderator = User::factory()->create(['is_moderator' => true, 'name' => 'Ola']);
        $this->actingAs($moderator);

        Livewire::test(ViewAnimalEdit::class, ['record' => $edit->getRouteKey()])
            ->mountAction('matchPreview')
            ->assertMountedActionModalSee('Nie pokazuj')
            ->call('toggleMatchExclusion', $absurd->id)
            ->assertMountedActionModalSee(['Przywróć', 'odrzucone (Ola)']);

        $this->assertDatabaseHas('match_exclusions', ['animal_edit_id' => $edit->id, 'candidate_animal_id' => $absurd->id, 'user_id' => $moderator->id]);

        // ** Zatwierdzenie - wykluczenie przechodzi na nowe ogłoszenie
        $animal = $this->approve($edit);
        $this->assertSame($animal->id, MatchExclusion::first()->animal_id);

        $this->artisan('match:notify')->assertSuccessful();

        Mail::assertSent(MatchCandidatesFound::class, fn (MatchCandidatesFound $m) => count($m->items) === 1);
        Mail::assertNotSent(NewSimilarListing::class, fn (NewSimilarListing $m) => $m->hasTo('absurd@x.pl'));
        Mail::assertSent(NewSimilarListing::class, fn (NewSimilarListing $m) => $m->hasTo('good@x.pl'));

        // ** W logu zostaje ślad do kalibracji progów
        $logged = MatchSearchResult::where('animal_id', $absurd->id)->first();
        $this->assertTrue($logged->excluded);
        $this->assertFalse($logged->shown);
    }

    public function test_odrzucenie_dziala_w_obie_strony_i_mozna_je_cofnac(): void
    {
        $lost = $this->animal();
        $found = $this->candidate('f@x.pl');
        $service = app(MatchExclusions::class);

        $this->assertTrue($service->toggle($lost, $found->id, null));

        // ** Druga strona: szukając dla znalezionego, zaginiony też jest odrzucony
        $this->assertTrue($service->for($found)->has($lost->id));

        $this->assertFalse($service->toggle($found, $lost->id, null));
        $this->assertSame(0, MatchExclusion::count());
    }

    public function test_okno_zatwierdzenia_przypomina_o_propozycjach_ai(): void
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        $this->results = [$this->row($this->candidate('a@x.pl'), 1, 'strong', 1)];

        $this->actingAs(User::factory()->create(['is_moderator' => true]));

        Livewire::test(ViewAnimalEdit::class, ['record' => $edit->getRouteKey()])
            ->mountAction('approve')
            ->assertMountedActionModalSee('Podobne ogłoszenia znalezione przez AI: 1');
    }
}
