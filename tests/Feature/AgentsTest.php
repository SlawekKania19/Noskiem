<?php

namespace Tests\Feature;

use App\Filament\Resources\AgentResource\Pages\ListAgents;
use App\Mail\AgentHelpRequest;
use App\Mail\AgentVerifyEmail;
use App\Models\AgentArea;
use App\Models\Animal;
use App\Models\User;
use App\Services\EmailDataEraser;
use App\Support\AgentsLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Feature\Matching\MatchingFixtures;
use Tests\TestCase;

// ---------------------------
// Agenci noskiem: rejestracja i potwierdzenie e-maila, obszary działania, prośby
// o pomoc z crona (agents:notify), przycisk "Szukam" z licznikiem i zakładka w panelu.
// Ogłoszenia testowe leżą w Katowicach (50.26, 19.02) - patrz MatchingFixtures.
// ---------------------------
class AgentsTest extends TestCase
{
    use MatchingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        Mail::fake();
    }

    private function agent(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['is_agent' => true], $attrs));
    }

    // ** Obszar wokół punktu ogłoszeń testowych; $offsetKm przesuwa środek na północ
    private function area(User $agent, int $radiusKm = 5, float $offsetKm = 0, array $attrs = []): AgentArea
    {
        return $agent->agentAreas()->create(array_merge([
            'latitude' => 50.26 + $offsetKm / 111.0, 'longitude' => 19.02, 'radius_km' => $radiusKm,
        ], $attrs));
    }

    public function test_rejestracja_tworzy_konto_agenta_i_wysyla_link_potwierdzajacy(): void
    {
        $this->get(route('agents.register'))->assertOk()->assertSee('Zostań agentem');

        $this->post(route('agents.store'), [
            'name' => 'Ola', 'email' => 'ola@example.com',
            'password' => 'Haslo1234', 'password_confirmation' => 'Haslo1234',
            'voivodeship_id' => $this->base['voivodeship_id'], 'city_id' => $this->base['city_id'],
            'terms' => '1',
        ])->assertRedirect(route('agent.panel'));

        $agent = User::where('email', 'ola@example.com')->first();

        $this->assertAuthenticatedAs($agent);
        $this->assertTrue($agent->is_agent);
        $this->assertFalse($agent->isActiveAgent());       // przed potwierdzeniem e-maila
        $this->assertFalse($agent->is_admin);
        Mail::assertSent(AgentVerifyEmail::class, fn ($m) => $m->hasTo('ola@example.com'));

        // ** Konto agenta nie daje dostępu do panelu admina
        $this->get('/admin')->assertForbidden();

        // ** Link z maila działa bez logowania
        auth()->logout();
        $this->get($agent->agentVerificationUrl())->assertRedirect(route('login'));
        $this->assertTrue($agent->fresh()->isActiveAgent());
    }

    public function test_link_potwierdzajacy_wymaga_podpisu(): void
    {
        $agent = $this->agent(['email_verified_at' => null]);

        $this->get('/agenci/potwierdz/'.$agent->id.'/'.sha1($agent->email))->assertForbidden();
        $this->assertNull($agent->fresh()->email_verified_at);
    }

    public function test_po_zalogowaniu_agent_trafia_do_swojego_panelu(): void
    {
        $agent = $this->agent(['password' => bcrypt('Haslo1234')]);

        $this->post('/login', ['email' => $agent->email, 'password' => 'Haslo1234'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('agent.panel'));
        $this->get(route('agent.panel'))->assertOk()->assertSee('Obszary działania');
    }

    public function test_agent_zarzadza_obszarami_tylko_swoimi(): void
    {
        $agent = $this->agent();
        $this->actingAs($agent);

        $this->post(route('agent.areas.store'), ['name' => 'Dom', 'latitude' => 50.26, 'longitude' => 19.02, 'radius_km' => 7])
            ->assertRedirect(route('agent.panel'));

        $area = $agent->agentAreas()->first();
        $this->assertSame(['Dom', 7], [$area->name, $area->radius_km]);

        $this->patch(route('agent.areas.update', $area), ['name' => 'Dom', 'radius_km' => 12]);
        $this->assertSame(12, $area->fresh()->radius_km);

        // ** Bez punktu, poza Polską i z za dużym promieniem - odrzucone
        $this->post(route('agent.areas.store'), ['radius_km' => 5])->assertSessionHasErrors('latitude');
        $this->post(route('agent.areas.store'), ['latitude' => 40.0, 'longitude' => 19.0, 'radius_km' => 5])->assertSessionHasErrors('latitude');
        $this->post(route('agent.areas.store'), ['latitude' => 50.26, 'longitude' => 19.02, 'radius_km' => 500])->assertSessionHasErrors('radius_km');

        // ** Cudzy obszar
        $foreign = $this->area($this->agent());
        $this->patch(route('agent.areas.update', $foreign), ['radius_km' => 1])->assertForbidden();
        $this->delete(route('agent.areas.destroy', $foreign))->assertForbidden();

        $this->delete(route('agent.areas.destroy', $area));
        $this->assertSame(0, $agent->agentAreas()->count());
    }

    public function test_cron_wysyla_prosbe_tylko_agentom_w_zasiegu_i_tylko_raz(): void
    {
        $near = $this->agent(['email' => 'near@example.com']);
        $this->area($near, 5, 3, ['name' => 'Dom']);            // 3 km od ogłoszenia, promień 5
        $far = $this->agent(['email' => 'far@example.com']);
        $this->area($far, 5, 20);                                // 20 km od ogłoszenia, promień 5

        $lost = $this->animal();
        $second = $this->animal('approved', ['animal_name' => 'Reks']);
        $this->animal('approved', ['status' => 'found']);        // znalezione - nie wysyłamy
        $this->animal('pending');                                // niezatwierdzone

        $this->artisan('agents:notify')->assertSuccessful();

        // ** Jeden zbiorczy mail z dwoma zaginionymi
        Mail::assertSent(AgentHelpRequest::class, 1);
        Mail::assertSent(AgentHelpRequest::class, fn (AgentHelpRequest $m) => $m->hasTo('near@example.com')
            && count($m->items) === 2
            && $m->items[0]['area_name'] === 'Dom'
            && abs($m->items[0]['distance_km'] - 3.0) < 0.2);

        $this->assertSame(2, $near->agentNotifications()->count());
        $this->assertNotNull($lost->fresh()->agent_notified_at);
        $this->assertNotNull($second->fresh()->agent_notified_at);

        // ** Kolejny przebieg nic nie wysyła
        $this->artisan('agents:notify');
        Mail::assertSent(AgentHelpRequest::class, 1);
    }

    public function test_cron_pomija_nieaktywnych_agentow_autora_i_stare_ogloszenia(): void
    {
        $this->area($this->agent(['email_verified_at' => null]));
        $this->area($this->agent(['agent_notify' => false]));
        $blocked = $this->agent();
        $blocked->forceFill(['agent_blocked_at' => now()])->save();
        $this->area($blocked);
        // ** Agent jest autorem ogłoszenia (ten sam e-mail, inna wielkość liter)
        $this->area($this->agent(['email' => 'Jan@Example.com']));

        $this->animal();
        $this->artisan('agents:notify');
        Mail::assertNothingSent();

        // ** Ogłoszenie starsze niż limit - nie rozsyłamy
        $active = $this->agent();
        $this->area($active);
        $old = $this->animal('approved', ['contact_email' => 'ktos@example.com']);
        Animal::whereKey($old->id)->toBase()->update(['created_at' => now()->subDays(10)]);

        $this->artisan('agents:notify');
        Mail::assertNothingSent();
    }

    public function test_agent_z_kilkoma_obszarami_dostaje_ogloszenie_raz_z_najblizszego(): void
    {
        $agent = $this->agent();
        $this->area($agent, 30, 20, ['name' => 'Praca']);
        $this->area($agent, 10, 2, ['name' => 'Dom']);
        $this->animal('approved', ['contact_email' => 'ktos@example.com']);

        $this->artisan('agents:notify');

        Mail::assertSent(AgentHelpRequest::class, fn (AgentHelpRequest $m) => count($m->items) === 1 && $m->items[0]['area_name'] === 'Dom');
    }

    public function test_przycisk_szukam_tylko_dla_aktywnego_agenta_i_licznik_dla_wszystkich(): void
    {
        $animal = $this->animal();

        // ** Gość: bez przycisku, bez licznika
        $this->get(route('animals.show', $animal))->assertOk()->assertDontSee('/szukam')->assertDontSee('już szuka');
        $this->post(route('agent.search.toggle', $animal))->assertRedirect(route('login'));

        // ** Agent bez potwierdzonego e-maila nie może kliknąć
        $this->actingAs($this->agent(['email_verified_at' => null]))
            ->post(route('agent.search.toggle', $animal))->assertForbidden();

        $agent = $this->agent();
        $this->actingAs($agent);
        $this->get(route('animals.show', $animal))->assertSee('Szukam');
        $this->post(route('agent.search.toggle', $animal));
        $this->assertSame(1, $animal->searchingAgents()->count());

        // ** Licznik na stronie ogłoszenia i na karcie na liście - także dla gościa
        auth()->logout();
        $this->get(route('animals.show', $animal))->assertSee('1 agent już szuka tego zwierzaka');

        // ** Ponowne kliknięcie wycofuje; ogłoszenie "znalezione" nie ma przycisku
        $this->actingAs($agent)->post(route('agent.search.toggle', $animal));
        $this->assertSame(0, $animal->searchingAgents()->count());

        $found = $this->animal('approved', ['status' => 'found']);
        $this->post(route('agent.search.toggle', $found))->assertNotFound();
    }

    public function test_odmiana_licznika(): void
    {
        $this->assertSame('1 agent już szuka', AgentsLabel::searching(1, short: true));
        $this->assertSame('3 agenci już szukają', AgentsLabel::searching(3, short: true));
        $this->assertSame('5 agentów już szuka', AgentsLabel::searching(5, short: true));
        $this->assertSame('12 agentów już szuka', AgentsLabel::searching(12, short: true));
        $this->assertSame('22 agenci już szukają tego zwierzaka', AgentsLabel::searching(22));
    }

    public function test_wylaczenie_powiadomien_z_maila_dopiero_po_kliknieciu_przycisku(): void
    {
        $agent = $this->agent();
        $url = URL::signedRoute('agents.opt-out', ['user' => $agent->id]);

        // ** Samo wejście (skaner poczty) niczego nie zmienia
        $this->get($url)->assertOk()->assertSee('Wyłącz powiadomienia');
        $this->assertTrue($agent->fresh()->agent_notify);

        $this->post($url)->assertOk();
        $this->assertFalse($agent->fresh()->agent_notify);

        $this->get(route('agents.opt-out', ['user' => $agent->id]))->assertForbidden();
    }

    public function test_usuniecie_konta_i_rezygnacja_z_subskrypcji_zabieraja_dane_agenta(): void
    {
        $agent = $this->agent(['email' => 'agent@example.com']);
        $this->area($agent);
        $agent->searchedAnimals()->attach($this->animal()->id, ['created_at' => now()]);

        app(EmailDataEraser::class)->erase('Agent@example.com');

        $this->assertDatabaseMissing('users', ['id' => $agent->id]);
        $this->assertDatabaseCount('agent_areas', 0);
        $this->assertDatabaseCount('agent_searches', 0);

        // ** Konto z rolą w panelu zostaje, traci tylko dane agenta
        $staff = $this->agent(['is_moderator' => true]);
        $this->area($staff);
        $staff->removeAgent();

        $this->assertFalse($staff->fresh()->is_agent);
        $this->assertTrue($staff->fresh()->is_moderator);
        $this->assertDatabaseCount('agent_areas', 0);
    }

    public function test_zakladka_agenci_w_panelu_tylko_dla_admina(): void
    {
        $agent = $this->agent(['name' => 'Ola Agentka']);
        $this->area($agent);
        $staff = User::factory()->create(['name' => 'Piotr Moderator', 'is_moderator' => true]);

        $this->actingAs($staff)->get('/admin/agenci')->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/agenci')->assertOk();

        Livewire::test(ListAgents::class)
            ->assertCanSeeTableRecords([$agent])
            ->assertCanNotSeeTableRecords([$staff])
            ->callTableAction('block', $agent);

        $this->assertFalse($agent->fresh()->isActiveAgent());

        // ** Agent nie pojawia się na liście kont panelu
        $this->get('/admin/users')->assertOk()->assertDontSee('Ola Agentka')->assertSee('Piotr Moderator');
    }
}
