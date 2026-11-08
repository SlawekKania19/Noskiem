<?php

namespace Tests\Feature\Chips;

use App\Filament\Resources\ChipRegistrationResource\Pages\ViewChipRegistration;
use App\Mail\ChipRegistrationApproved;
use App\Mail\ChipRegistrationRejected;
use App\Models\Breed;
use App\Models\ChipRegistration;
use App\Models\City;
use App\Models\ModerationLog;
use App\Models\Species;
use App\Models\User;
use App\Models\Voivodeship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

// ---------------------------
// Moderacja bazy chipów w Filament (ChipRegistrationResource, ChipModerationService)
// ---------------------------

class ChipModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');

        $this->moderator = User::factory()->create(['is_moderator' => true]);
    }

    // ** Wpis z potwierdzonym e-mailem (widoczny dla moderatora) i plikiem naklejki na dysku
    private function registration(array $overrides = []): ChipRegistration
    {
        $species = Species::firstOrCreate(['name_pl' => 'Pies'], ['name_en' => 'Dog']);
        $voivodeship = Voivodeship::firstOrCreate(['name_pl' => 'śląskie'], ['name_en' => 'Silesia']);

        $path = 'chip-stickers/'.Str::random(10).'.jpg';
        Storage::disk('local')->put($path, 'jpg');

        return ChipRegistration::create(array_merge([
            'chip_number'        => '616093900123456',
            'animal_name'        => 'Burek',
            'species_id'         => $species->id,
            'breed_id'           => Breed::firstOrCreate(['breed_pl' => 'Beagle'], ['breed_en' => 'Beagle', 'species_id' => $species->id])->id,
            'voivodeship_id'     => $voivodeship->id,
            'city_id'            => City::firstOrCreate(['name_pl' => 'Katowice'], ['name_en' => 'Katowice', 'voivodeship_id' => $voivodeship->id])->id,
            'contact_phone'      => '600123456',
            'contact_email'      => 'wlasciciel@example.com',
            'sticker_photo_path' => $path,
            'mod_status'         => 'pending',
            'edit_token'         => Str::random(64),
            'email_verified_at'  => now(),
        ], $overrides));
    }

    public function test_list_shows_only_email_verified_registrations(): void
    {
        $this->registration(['animal_name' => 'Potwierdzony']);
        $this->registration(['animal_name' => 'Niepotwierdzony', 'email_verified_at' => null]);

        $this->actingAs($this->moderator)->get('/admin/chip-registrations')
            ->assertOk()
            ->assertSee('Potwierdzony')
            ->assertDontSee('Niepotwierdzony');
    }

    public function test_users_without_moderation_role_have_no_access(): void
    {
        $author = User::factory()->create(['is_author' => true]);

        $this->actingAs($author)->get('/admin/chip-registrations')->assertForbidden();
    }

    public function test_view_page_warns_about_chip_number_conflict(): void
    {
        $this->registration(['mod_status' => 'approved', 'animal_name' => 'Starszy']);
        $new = $this->registration();

        $this->actingAs($this->moderator)->get('/admin/chip-registrations/'.$new->id)
            ->assertOk()
            ->assertSee('Konflikt numeru chipa')
            ->assertSee('Starszy');
    }

    public function test_approve_makes_registration_searchable_and_notifies_owner(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->moderator);
        Livewire::test(ViewChipRegistration::class, ['record' => $registration->id])
            ->callAction('approve');

        $this->assertSame('approved', $registration->fresh()->mod_status);
        $this->assertTrue(ModerationLog::where('chip_registration_id', $registration->id)->where('action', 'approved')->exists());
        Mail::assertSent(ChipRegistrationApproved::class, fn ($mail) => $mail->hasTo('wlasciciel@example.com'));

        $this->getJson('/baza-chipow/szukaj?chip=616093900123456')->assertJson(['found' => true]);
    }

    public function test_reject_saves_reason_and_notifies_owner(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->moderator);
        Livewire::test(ViewChipRegistration::class, ['record' => $registration->id])
            ->callAction('reject', ['reason' => 'Nieczytelne zdjęcie naklejki']);

        $registration->refresh();
        $this->assertSame('rejected', $registration->mod_status);
        $this->assertSame('Nieczytelne zdjęcie naklejki', $registration->mod_reject_reason);
        Mail::assertSent(ChipRegistrationRejected::class);
    }

    public function test_delete_removes_sticker_file(): void
    {
        $registration = $this->registration();
        $path = $registration->sticker_photo_path;

        $this->actingAs($this->moderator);
        Livewire::test(ViewChipRegistration::class, ['record' => $registration->id])
            ->callAction('delete');

        $this->assertNull(ChipRegistration::find($registration->id));
        Storage::disk('local')->assertMissing($path);
    }
}
