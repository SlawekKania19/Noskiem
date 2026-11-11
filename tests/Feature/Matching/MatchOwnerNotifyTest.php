<?php

namespace Tests\Feature\Matching;

use App\Mail\MatchCandidatesFound;
use App\Mail\NewSimilarListing;
use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\MatchEvent;
use App\Models\MatchOwnerNotice;
use App\Models\MatchRequest;
use App\Services\PhotoMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

// ---------------------------
// Mail do autora STAREGO ogłoszenia o nowym podobnym (MatchOwnerNotifier -> NewSimilarListing),
// wysyłany przy match:notify obok maila do autora nowego ogłoszenia.
// Wyszukiwanie podmienione atrapą - jak w MatchNotifyTest.
// ---------------------------

class MatchOwnerNotifyTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    private array $nextResult = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        config(['noskiem_match.notify.owners_enabled' => true, 'noskiem_match.notify.owners_cooldown_days' => 3]);
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
        return $this->nextResult + ['status' => 'ok', 'results' => [], 'model_version' => $this->mv,
            'params' => ['filters' => ['radius_km' => 150]],
            'n_candidates' => 0, 'n_compared' => 0, 'n_pending_embedding' => 0];
    }

    // ** Wiersz wyniku ponad progiem na danej pozycji
    private function row(Animal $animal, int $position = 1, string $level = 'possible'): array
    {
        return ['listing_id' => $animal->id, 'score' => 0.65, 'bonus' => 0.0, 'level' => $level, 'shown' => true,
            'position' => $position, 'rank' => $position, 'breed_match' => null, 'color_overlap' => null,
            'best_photo_index' => 0, 'distance_km' => 8.4];
    }

    // ** Stare ogłoszenie (znaleziony) - kandydat, którego autor dostaje mail
    private function owner(array $attrs = []): Animal
    {
        static $n = 0;
        $n++;

        return $this->animal('approved', $attrs + ['status' => 'found', 'contact_name' => 'Anna Nowak',
            'contact_email' => "anna{$n}@example.com"]);
    }

    // ** Nowe zgłoszenie ze zleceniem, zatwierdzone; zwraca nowe ogłoszenie
    private function newListing(string $email = 'jan@example.com'): Animal
    {
        $edit = $this->edit(attrs: ['contact_email' => $email]);
        $this->photo(['animal_edit_id' => $edit->id]);
        MatchRequest::create(['animal_edit_id' => $edit->id]);

        $animal = $this->approve($edit);
        $this->photo(['animal_id' => $animal->id, 'is_main' => true]);

        return $animal;
    }

    public function test_autor_starego_ogloszenia_dostaje_mail_o_nowym(): void
    {
        $owner = $this->owner();
        $new = $this->newListing();
        $this->nextResult = ['results' => [$this->row($owner)]];

        $this->artisan('match:notify')->assertSuccessful();

        Mail::assertSent(MatchCandidatesFound::class);
        Mail::assertSent(NewSimilarListing::class, function (NewSimilarListing $mail) use ($owner) {
            return $mail->hasTo($owner->contact_email)
                && $mail->animal->is($owner)
                && str_contains($mail->item['url'], '/dopasowania/')
                && str_contains($mail->item['url'], 'signature=')
                && $mail->item['photo_url'] !== null
                && str_contains($mail->links['opt_out'], 'signature=')
                && str_contains($mail->links['manage'], 'token='.$owner->edit_token);
        });

        $notice = MatchOwnerNotice::first();
        $this->assertSame([$owner->id, $new->id, 'sent', 'possible'],
            [$notice->animal_id, $notice->query_animal_id, $notice->status, $notice->level]);
        $this->assertSame('notified', MatchRequest::first()->status);
    }

    public function test_pomija_nieaktywne_wypisane_i_tego_samego_autora(): void
    {
        $resolved = $this->owner(['mod_status' => 'resolved']);
        $optedOut = $this->owner(['match_notify_opt_out_at' => now()]);
        $sameAuthor = $this->owner(['contact_email' => 'JAN@example.com']);
        $ok = $this->owner();
        $this->newListing();

        $this->nextResult = ['results' => [
            $this->row($resolved, 1), $this->row($optedOut, 2), $this->row($sameAuthor, 3), $this->row($ok, 4),
        ]];
        $this->artisan('match:notify');

        Mail::assertSent(NewSimilarListing::class, 1);
        Mail::assertSent(NewSimilarListing::class, fn ($mail) => $mail->hasTo($ok->contact_email));
        $this->assertSame([$ok->id], MatchOwnerNotice::pluck('animal_id')->all());
    }

    public function test_wylaczone_w_ustawieniach(): void
    {
        config(['noskiem_match.notify.owners_enabled' => false]);
        $owner = $this->owner();
        $this->newListing();
        $this->nextResult = ['results' => [$this->row($owner)]];

        $this->artisan('match:notify');

        Mail::assertSent(MatchCandidatesFound::class);
        Mail::assertNotSent(NewSimilarListing::class);
    }

    public function test_najwyzej_jeden_mail_na_kilka_dni_dla_ogloszenia(): void
    {
        $owner = $this->owner();
        $this->nextResult = ['results' => [$this->row($owner)]];

        $this->newListing();
        $this->artisan('match:notify');

        // ** Kolejne podobne ogłoszenie po 2 dniach - tylko wpis w logu
        $this->travel(2)->days();
        $second = $this->newListing();
        $this->artisan('match:notify');

        Mail::assertSent(NewSimilarListing::class, 1);
        $this->assertSame('cooldown', MatchOwnerNotice::where('query_animal_id', $second->id)->value('status'));

        // ** Po upływie limitu (liczonego od ostatniego WYSŁANEGO maila) - znowu piszemy
        $this->travel(2)->days();
        $this->newListing();
        $this->artisan('match:notify');

        Mail::assertSent(NewSimilarListing::class, 2);
    }

    public function test_ta_sama_para_ogloszen_tylko_raz(): void
    {
        config(['noskiem_match.notify.owners_cooldown_days' => 0]);
        $owner = $this->owner();
        $new = $this->newListing();
        $this->nextResult = ['results' => [$this->row($owner)]];
        $this->artisan('match:notify');

        // ** Ponowne zlecenie dla tego samego ogłoszenia (np. zatwierdzona edycja)
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id]);
        MatchRequest::create(['animal_edit_id' => $edit->id]);
        $edit->update(['mod_status' => 'approved', 'animal_id' => $new->id]);
        $this->artisan('match:notify');

        Mail::assertSent(MatchCandidatesFound::class, 2);
        Mail::assertSent(NewSimilarListing::class, 1);
    }

    public function test_klik_w_mailu_prowadzi_do_nowego_ogloszenia_i_jest_logowany(): void
    {
        $owner = $this->owner();
        $new = $this->newListing();
        $this->nextResult = ['results' => [$this->row($owner)]];
        $this->artisan('match:notify');

        $url = null;
        Mail::assertSent(NewSimilarListing::class, function ($mail) use (&$url) {
            $url = $mail->item['url'];

            return true;
        });

        $this->get($url)->assertRedirect(route('animals.show', $new));

        $event = MatchEvent::first();
        $this->assertSame(['email_owner', $owner->id, $new->id], [$event->source, $event->animal_id, $event->query_animal_id]);

        // ** Bez podpisu - 403
        $this->get(strtok($url, '?'))->assertForbidden();
    }

    public function test_wypisanie_wymaga_klikniecia_przycisku(): void
    {
        $owner = $this->owner();
        $url = URL::signedRoute('matches.opt-out', ['animal' => $owner->id]);

        // ** Samo wejście (np. skaner poczty) niczego nie zapisuje
        $this->get($url)->assertOk()->assertSee('Nie wysyłaj mi więcej takich powiadomień');
        $this->assertNull($owner->refresh()->match_notify_opt_out_at);

        $this->post($url)->assertOk()->assertSee('Gotowe');
        $this->assertNotNull($owner->refresh()->match_notify_opt_out_at);

        // ** Bez podpisu - 403
        $this->get('/dopasowania/wypisz/'.$owner->id)->assertForbidden();
        $this->post('/dopasowania/wypisz/'.$owner->id)->assertForbidden();
    }

    public function test_tresc_maila(): void
    {
        $owner = $this->owner(['status' => 'lost', 'animal_name' => 'Burek']);
        $html = (new NewSimilarListing($owner, [
            'title' => 'Znaleziony pies - Katowice', 'url' => 'https://noskiem.test/dopasowania/1/nowe/1',
            'photo_url' => 'https://noskiem.test/storage/photos/a.jpg', 'photo_position' => '50% 30%',
            'level' => 'strong', 'location' => 'Park', 'date' => '05.11.2026', 'distance_km' => 8.4,
        ], ['opt_out' => 'https://noskiem.test/wypisz', 'manage' => 'https://noskiem.test/edycja']))->render();

        $this->assertStringContainsString('Witaj, Anna!', $html);
        $this->assertStringContainsString('o znalezionym zwierzaku', $html);
        $this->assertStringContainsString('Twojego ogłoszenia (Burek).', $html);
        $this->assertStringContainsString('Bardzo podobne', $html);
        $this->assertStringContainsString('ok. 8 km od miejsca', $html);
        $this->assertStringContainsString('może się mylić', $html);
        $this->assertStringContainsString('https://noskiem.test/wypisz', $html);
        $this->assertStringContainsString('https://noskiem.test/edycja', $html);
    }
}
