<?php

namespace Tests\Feature\Photos;

use App\Filament\Resources\AnimalEditResource\Pages\ViewAnimalEdit;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Matching\MatchingFixtures;
use Tests\TestCase;

// ---------------------------
// Podgląd karty ogłoszenia 1:1 dla panelu (route admin.card-preview, ramka w Filament)
// ---------------------------
class CardPreviewTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        $this->withoutVite();
    }

    public function test_tylko_admin_i_moderator_widza_podglad(): void
    {
        $animal = $this->animal('approved');
        $url = route('admin.card-preview', ['type' => 'animal', 'id' => $animal->id]);

        $this->get($url)->assertRedirect();
        $this->actingAs(User::factory()->create(['is_author' => true]))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_moderator' => true]))->get($url)->assertOk();
    }

    public function test_karta_ogloszenia_z_kadrem_zdjecia(): void
    {
        $animal = $this->animal('approved', ['status' => 'found']);
        $photo = $this->photo(['animal_id' => $animal->id, 'is_main' => true]);
        $photo->forceFill(['width' => 1000, 'height' => 2000, 'focus_manual' => [0.5, 0.35]])->save();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.card-preview', ['type' => 'animal', 'id' => $animal->id]))
            ->assertOk()
            ->assertSee('Znaleziony')
            ->assertSee($animal->generated_title)
            ->assertSee('object-position: 50% 20%', false);
    }

    public function test_zgloszenie_przed_moderacja_wyglada_jak_przyszla_karta(): void
    {
        $edit = $this->edit();
        $this->photo(['animal_edit_id' => $edit->id, 'is_main' => true]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.card-preview', ['type' => 'edit', 'id' => $edit->id]))
            ->assertOk()
            ->assertSee('Zaginiony')
            ->assertSee('Mieszaniec')
            ->assertSee('object-position: 50% 30%', false);
    }

    public function test_tryb_osadzony_pokazuje_wybrane_zdjecie_i_nasluchuje_kadru(): void
    {
        $animal = $this->animal('approved');
        $main = $this->photo(['animal_id' => $animal->id, 'is_main' => true]);
        $other = $this->photo(['animal_id' => $animal->id, 'is_main' => false]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.card-preview', ['type' => 'animal', 'id' => $animal->id, 'photo' => $other->id, 'embed' => 1]))
            ->assertOk()
            ->assertSee($other->path)
            ->assertDontSee($main->path)
            ->assertSee('noskiem-card-focus')
            ->assertDontSee('Telefon');

        // ** Podgląd niczego nie zapisuje
        $this->assertTrue($main->refresh()->is_main);
        $this->assertFalse($other->refresh()->is_main);
    }

    public function test_okno_kadrow_pokazuje_prawdziwa_karte_obok_zdjecia(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $edit = $this->edit();
        $photo = $this->photo(['animal_edit_id' => $edit->id, 'is_main' => true]);

        Livewire::actingAs($admin)
            ->test(ViewAnimalEdit::class, ['record' => $edit->id])
            ->mountAction('photoFocus')
            ->assertMountedActionModalSee('Tak będzie wyglądać karta na stronie')
            ->assertMountedActionModalSeeHtml('/podglad-karty/edit/'.$edit->id.'?photo='.$photo->id.'&amp;embed=1');
    }
}
