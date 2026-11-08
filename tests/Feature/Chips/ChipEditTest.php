<?php

namespace Tests\Feature\Chips;

use App\Filament\Resources\ChipRegistrationResource\Pages\ViewChipRegistration;
use App\Mail\ChipRegistrationApproved;
use App\Mail\ChipRegistrationRejected;
use App\Models\Breed;
use App\Models\ChipRegistration;
use App\Models\City;
use App\Models\Species;
use App\Models\User;
use App\Models\Voivodeship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

// ---------------------------
// Edycja/usunięcie wpisu w bazie chipów przez właściciela (token) i moderacja
// edycji w Filament (ChipRegistrationController::edit/update/destroy, ChipModerationService)
// ---------------------------

class ChipEditTest extends TestCase
{
    use RefreshDatabase;

    private ChipRegistration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');

        $species = Species::forceCreate(['name_pl' => 'Pies', 'name_en' => 'Dog']);
        $voivodeship = Voivodeship::forceCreate(['name_pl' => 'śląskie', 'name_en' => 'Silesia']);

        Storage::disk('local')->put('chip-stickers/stara.jpg', 'jpg');

        $this->registration = ChipRegistration::create([
            'chip_number'        => '616093900123456',
            'animal_name'        => 'Burek',
            'species_id'         => $species->id,
            'breed_id'           => Breed::forceCreate(['breed_pl' => 'Beagle', 'breed_en' => 'Beagle', 'species_id' => $species->id])->id,
            'voivodeship_id'     => $voivodeship->id,
            'city_id'            => City::forceCreate(['name_pl' => 'Katowice', 'name_en' => 'Katowice', 'voivodeship_id' => $voivodeship->id])->id,
            'contact_phone'      => '600123456',
            'contact_email'      => 'wlasciciel@example.com',
            'sticker_photo_path' => 'chip-stickers/stara.jpg',
            'mod_status'         => 'approved',
            'edit_token'         => Str::random(64),
            'email_verified_at'  => now(),
        ]);
    }

    // ** Dane formularza edycji = obecne dane wpisu + nadpisania
    private function payload(array $overrides = []): array
    {
        return array_merge(
            $this->registration->only(['chip_number', 'animal_name', 'species_id', 'breed_id', 'voivodeship_id', 'city_id', 'contact_phone', 'contact_email']),
            ['token' => $this->registration->edit_token],
            $overrides,
        );
    }

    private function editUrl(): string
    {
        return route('chips.edit', ['chipRegistration' => $this->registration, 'token' => $this->registration->edit_token]);
    }

    public function test_edit_page_requires_valid_token(): void
    {
        $this->get($this->editUrl())->assertOk()->assertSee('Burek');

        $this->get(route('chips.edit', ['chipRegistration' => $this->registration, 'token' => 'zly']))->assertForbidden();
        $this->post(route('chips.update', $this->registration), $this->payload(['token' => 'zly']))->assertForbidden();
        $this->post(route('chips.destroy', $this->registration), ['token' => 'zly'])->assertForbidden();
    }

    public function test_edit_of_approved_registration_waits_for_moderation(): void
    {
        $this->post(route('chips.update', $this->registration), $this->payload(['contact_phone' => '700999888']))
            ->assertRedirect($this->editUrl())
            ->assertSessionHasNoErrors();

        $this->registration->refresh();
        $this->assertSame('600123456', $this->registration->contact_phone);
        $this->assertSame('700999888', $this->registration->pending_changes['contact_phone']);

        // ** Wyszukiwarka/telefon nadal ze starej wersji
        $this->getJson('/baza-chipow/telefon?chip=616093900123456')->assertJsonFragment(['phone' => $this->registration->formatted_phone]);
    }

    public function test_edit_of_pending_registration_is_applied_directly(): void
    {
        $this->registration->update(['mod_status' => 'pending']);

        $this->post(route('chips.update', $this->registration), $this->payload(['animal_name' => 'Azor']));

        $this->registration->refresh();
        $this->assertSame('Azor', $this->registration->animal_name);
        $this->assertNull($this->registration->pending_changes);
    }

    public function test_moderator_approves_changes_with_new_sticker(): void
    {
        $this->post(route('chips.update', $this->registration), $this->payload([
            'animal_name'   => 'Azor',
            'sticker_photo' => UploadedFile::fake()->image('nowa.jpg'),
        ]));
        $newSticker = $this->registration->fresh()->pending_changes['sticker_photo_path'];

        $this->actingAs(User::factory()->create(['is_moderator' => true]));
        Livewire::test(ViewChipRegistration::class, ['record' => $this->registration->id])
            ->assertSee('Proponowane zmiany')
            ->callAction('approveChanges');

        $this->registration->refresh();
        $this->assertSame('Azor', $this->registration->animal_name);
        $this->assertSame($newSticker, $this->registration->sticker_photo_path);
        $this->assertNull($this->registration->pending_changes);
        Storage::disk('local')->assertMissing('chip-stickers/stara.jpg');
        Mail::assertSent(ChipRegistrationApproved::class, fn ($mail) => $mail->isEdit);
    }

    public function test_moderator_rejects_changes_and_registration_stays_unchanged(): void
    {
        $this->post(route('chips.update', $this->registration), $this->payload([
            'animal_name'   => 'Azor',
            'sticker_photo' => UploadedFile::fake()->image('nowa.jpg'),
        ]));
        $newSticker = $this->registration->fresh()->pending_changes['sticker_photo_path'];

        $this->actingAs(User::factory()->create(['is_moderator' => true]));
        Livewire::test(ViewChipRegistration::class, ['record' => $this->registration->id])
            ->callAction('rejectChanges', ['reason' => 'Nieczytelna naklejka']);

        $this->registration->refresh();
        $this->assertSame('Burek', $this->registration->animal_name);
        $this->assertSame('approved', $this->registration->mod_status);
        $this->assertNull($this->registration->pending_changes);
        Storage::disk('local')->assertMissing($newSticker);
        Storage::disk('local')->assertExists('chip-stickers/stara.jpg');
        Mail::assertSent(ChipRegistrationRejected::class, fn ($mail) => $mail->isEdit && $mail->reason === 'Nieczytelna naklejka');
    }

    public function test_owner_can_delete_registration(): void
    {
        $this->post(route('chips.destroy', $this->registration), ['token' => $this->registration->edit_token])
            ->assertRedirect(route('chips.index'));

        $this->assertNull(ChipRegistration::find($this->registration->id));
        Storage::disk('local')->assertMissing('chip-stickers/stara.jpg');
    }

    public function test_approval_mail_contains_edit_link(): void
    {
        $html = (new ChipRegistrationApproved($this->registration))->render();

        $this->assertStringContainsString(e($this->editUrl()), $html);
    }
}
