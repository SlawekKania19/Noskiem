<?php

namespace Tests\Feature\Matching;

use App\Filament\Resources\AnimalEditResource\Pages\ViewAnimalEdit;
use App\Filament\Resources\AnimalResource\Pages\ViewAnimal;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchEvent;
use App\Models\MatchRequest;
use App\Models\MatchSearch;
use App\Models\User;
use App\Services\MatchPreview;
use App\Services\PhotoMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// ---------------------------
// Panel "Podobne (AI)" na podglądzie zgłoszenia i ogłoszenia (MatchPreviewAction,
// MatchPreview). Wyszukiwanie podmienione atrapą.
// ---------------------------

class MatchPreviewTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    private array $nextResult = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();

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

    private function row(Animal $a, string $level, ?int $position, float $score): array
    {
        return ['listing_id' => $a->id, 'score' => $score, 'bonus' => 0.0, 'rank' => $position ?? 9, 'level' => $level,
            'shown' => $position !== null, 'position' => $position, 'breed_match' => true,
            'color_overlap' => 1.0, 'best_photo_index' => 0, 'distance_km' => 0.4];
    }

    private function ok(array $results): array
    {
        return ['status' => 'ok', 'model_version' => $this->mv, 'results' => $results,
            'params' => ['threshold_possible' => 0.6, 'threshold_strong' => 0.75, 'filters' => ['radius_km' => 150]],
            'n_candidates' => 2, 'n_compared' => 2, 'n_pending_embedding' => 0];
    }

    public function test_moderator_widzi_podglad_maila_przed_zatwierdzeniem(): void
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        MatchRequest::create(['animal_edit_id' => $edit->id]);
        $strong = $this->animal('approved', ['status' => 'found', 'location_text' => 'Pod Mostem', 'contact_email' => 'a@example.com']);
        $weak = $this->animal('approved', ['status' => 'found', 'location_text' => 'Daleko Stąd', 'contact_email' => 'b@example.com']);
        $this->nextResult = $this->ok([$this->row($strong, 'strong', 1, 0.894), $this->row($weak, 'below', null, 0.131)]);

        $this->actingAs(User::factory()->create(['is_moderator' => true]));

        Livewire::test(ViewAnimalEdit::class, ['record' => $edit->getRouteKey()])
            ->assertActionVisible('matchPreview')
            ->mountAction('matchPreview')
            ->assertMountedActionModalSee([
                'Podgląd przed zatwierdzeniem',
                'Pod Mostem',
                '0,894',
                'Pokaż też poniżej progu (1)',
                'czeka (na zatwierdzenie',
            ]);
    }

    public function test_historia_maila_z_kliknieciami_na_ogloszeniu(): void
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        $req = MatchRequest::create(['animal_edit_id' => $edit->id, 'status' => 'notified', 'results_shown' => 1, 'processed_at' => now()]);
        $listing = $this->approve($edit);
        $cand = $this->animal('approved', ['status' => 'found', 'location_text' => 'Pod Mostem']);

        $search = MatchSearch::create([
            'match_request_id' => $req->id, 'query_animal_edit_id' => $edit->id, 'query_animal_id' => $listing->id,
            'model_version' => $this->mv, 'channel' => 'notify', 'params' => [], 'n_candidates' => 1,
            'n_compared' => 1, 'n_pending_embedding' => 0, 'n_shown' => 1, 'duration_ms' => 5,
        ]);
        $search->results()->create(['animal_id' => $cand->id, 'score' => 0.88, 'bonus' => 0, 'rank' => 1,
            'level' => 'strong', 'shown' => true, 'position' => 1, 'distance_km' => 0.4, 'best_photo_index' => 0]);
        MatchEvent::create(['match_search_id' => $search->id, 'event' => 'click', 'source' => 'email', 'position' => 1,
            'query_animal_edit_id' => $edit->id, 'animal_id' => $cand->id, 'model_version' => $this->mv]);

        $this->nextResult = ['status' => 'pending'] + $this->ok([]);
        $data = app(MatchPreview::class)->build($listing);

        $this->assertFalse($data['is_pending']);
        $this->assertSame('pending', $data['live']['status']);
        $this->assertSame('notified', $data['request']->status);
        $this->assertCount(1, $data['history']);
        $this->assertSame([1, 1, 'strong'], [$data['history'][0]['rows'][0]['position'],
            $data['history'][0]['rows'][0]['clicks'], $data['history'][0]['rows'][0]['level']]);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ViewAnimal::class, ['record' => $listing->getRouteKey()])
            ->mountAction('matchPreview')
            ->assertMountedActionModalSee([
                'Wyniki na teraz',
                'Zdjęcia czekają w kolejce',
                'mail wysłany',
                'kliknięte w mailu: 1×',
            ]);
    }

    public function test_blad_wyszukiwania_nie_psuje_panelu(): void
    {
        $edit = $this->edit();
        $this->app->instance(PhotoMatchService::class, new class extends PhotoMatchService {
            public function __construct() {}

            public function search(Animal|AnimalEdit $query): array
            {
                throw new \RuntimeException('python padł');
            }
        });

        $data = app(MatchPreview::class)->build($edit);

        $this->assertSame(['error', 'python padł'], [$data['live']['status'], $data['live']['error']]);
    }

    public function test_przycisk_ukryty_przy_wylaczonym_module(): void
    {
        $animal = $this->animal('approved');

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        config(['noskiem_match.enabled' => false]);
        Livewire::test(ViewAnimal::class, ['record' => $animal->getRouteKey()])
            ->assertActionHidden('matchPreview');
    }
}
