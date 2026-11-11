<?php

namespace Tests\Feature\Matching;

use App\Filament\Pages\MatchAi;
use App\Filament\Widgets\MatchStatusWidget;
use App\Models\Setting;
use App\Models\User;
use App\Support\MatchSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// ---------------------------
// Ustawienia dopasowań z panelu "Dopasowanie AI" (MatchSettings + strona Filament)
// ---------------------------

class MatchSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ** Boot aplikacji był przed migracją - nakładamy ustawienia na świeżą bazę
        MatchSettings::apply();
    }

    public function test_wartosc_z_panelu_nadpisuje_config_a_reset_przywraca_plik(): void
    {
        $default = config('noskiem_match.search.filters.radius_km');

        Setting::set('match.search.filters.radius_km', '42');
        Setting::set('match.queue.paused', '1');
        MatchSettings::apply();

        $this->assertSame(42.0, config('noskiem_match.search.filters.radius_km'));
        $this->assertTrue(config('noskiem_match.queue.paused'));

        MatchSettings::reset();

        $this->assertSame($default, config('noskiem_match.search.filters.radius_km'));
        $this->assertFalse(config('noskiem_match.queue.paused'));
        $this->assertSame(0, Setting::where('key', 'like', 'match.%')->count());
    }

    public function test_zapis_wartosci_rownej_domyslnej_nie_tworzy_wpisu(): void
    {
        $values = MatchSettings::current();
        $values['queue.batch_limit'] = 7;

        MatchSettings::save($values);

        // ** Tylko zmienione pole trafia do settings - reszta dalej idzie z .env
        $this->assertSame(['queue.batch_limit'], MatchSettings::overridden());
        $this->assertSame(7, config('noskiem_match.queue.batch_limit'));
    }

    public function test_strona_tylko_dla_admina(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin/dopasowanie-ai')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/dopasowanie-ai')
            ->assertOk()
            ->assertSee('Wstrzymaj przetwarzanie');
    }

    public function test_widget_statusu_renderuje_sie(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(MatchStatusWidget::class)
            ->assertOk()
            ->assertSee('Worker')
            ->assertSee('Zdjęcia w kolejce')
            ->assertSee('Maile z dopasowaniami');
    }

    public function test_zapis_z_panelu(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(MatchAi::class)
            ->set('data.queue.paused', true)
            ->set('data.search.threshold_possible', 0.55)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(config('noskiem_match.queue.paused'));
        $this->assertSame(0.55, config('noskiem_match.search.threshold_possible'));
        $this->assertEqualsCanonicalizing(
            ['queue.paused', 'search.threshold_possible'],
            MatchSettings::overridden(),
        );
    }

    public function test_walidacja_progow_i_okna_godzin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(MatchAi::class)
            ->set('data.search.threshold_possible', 0.8)
            ->set('data.search.threshold_strong', 0.7)
            ->call('save')
            ->assertHasErrors(['data.search.threshold_strong']);

        Livewire::test(MatchAi::class)
            ->set('data.queue.hours', '22-6')
            ->call('save')
            ->assertHasErrors(['data.queue.hours']);

        $this->assertSame([], MatchSettings::overridden());
    }
}
