<?php

namespace Tests\Feature\Chips;

use App\Mail\ChipRegistrationReceived;
use App\Mail\NewChipRegistrationForModeration;
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
use Tests\TestCase;

// ---------------------------
// Bezpłatna baza chipów - rejestracja, potwierdzenie e-mail (ChipRegistrationController)
// ---------------------------

class ChipRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Species $species;
    private Breed $breed;
    private Voivodeship $voivodeship;
    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');

        $this->species = Species::forceCreate(['name_pl' => 'Pies', 'name_en' => 'Dog']);
        $this->breed = Breed::forceCreate(['breed_pl' => 'Beagle', 'breed_en' => 'Beagle', 'species_id' => $this->species->id]);
        $this->voivodeship = Voivodeship::forceCreate(['name_pl' => 'śląskie', 'name_en' => 'Silesia']);
        $this->city = City::forceCreate(['name_pl' => 'Katowice', 'name_en' => 'Katowice', 'voivodeship_id' => $this->voivodeship->id]);
    }

    // ** Poprawny zestaw danych formularza - testy nadpisują pojedyncze pola
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'chip_number'    => '616 093 900 123 456',
            'animal_name'    => 'Burek',
            'species_id'     => $this->species->id,
            'breed_id'       => $this->breed->id,
            'voivodeship_id' => $this->voivodeship->id,
            'city_id'        => $this->city->id,
            'contact_phone'  => '600123456',
            'contact_email'  => 'wlasciciel@example.com',
            'sticker_photo'  => UploadedFile::fake()->image('naklejka.jpg'),
            'accept_terms'   => '1',
        ], $overrides);
    }

    public function test_page_renders(): void
    {
        $this->get('/baza-chipow')
            ->assertOk()
            ->assertSee('Zarejestruj chip');
    }

    public function test_store_saves_pending_registration_with_private_sticker_and_sends_confirmation(): void
    {
        $this->post('/baza-chipow', $this->payload())
            ->assertRedirect(route('chips.index'))
            ->assertSessionHasNoErrors();

        $registration = ChipRegistration::sole();

        // ** Spacje z naklejki usunięte przed zapisem
        $this->assertSame('616093900123456', $registration->chip_number);
        $this->assertSame('pending', $registration->mod_status);
        $this->assertNull($registration->email_verified_at);
        Storage::disk('local')->assertExists($registration->sticker_photo_path);

        Mail::assertSent(ChipRegistrationReceived::class, fn ($mail) => $mail->hasTo('wlasciciel@example.com'));
        Mail::assertNotSent(NewChipRegistrationForModeration::class);
    }

    public function test_shorter_chip_number_is_accepted_but_letters_are_not(): void
    {
        $this->post('/baza-chipow', $this->payload(['chip_number' => '123456789']))
            ->assertSessionHasNoErrors();

        $this->post('/baza-chipow', $this->payload(['chip_number' => '12345ABC9']))
            ->assertSessionHasErrors('chip_number');
    }

    public function test_breed_must_belong_to_selected_species(): void
    {
        $cat = Species::forceCreate(['name_pl' => 'Kot', 'name_en' => 'Cat']);

        $this->post('/baza-chipow', $this->payload(['species_id' => $cat->id]))
            ->assertSessionHasErrors('breed_id');
    }

    public function test_honeypot_does_not_save(): void
    {
        $this->post('/baza-chipow', $this->payload(['website' => 'spam']))
            ->assertRedirect(route('chips.index'));

        $this->assertSame(0, ChipRegistration::count());
    }

    public function test_confirm_email_notifies_moderators_once(): void
    {
        User::factory()->create(['is_moderator' => true]);
        $this->post('/baza-chipow', $this->payload());
        $registration = ChipRegistration::sole();

        $url = route('chips.confirm', ['chipRegistration' => $registration, 'token' => $registration->edit_token]);

        $this->get($url)->assertRedirect(route('chips.index'));
        $this->get($url);

        $this->assertNotNull($registration->fresh()->email_verified_at);
        Mail::assertSent(NewChipRegistrationForModeration::class, 1);
    }

    // ** Zapisany przez formularz i ręcznie zatwierdzony - moderacja dojdzie w Filament
    private function approvedRegistration(): ChipRegistration
    {
        $this->post('/baza-chipow', $this->payload(['additional_info' => 'Boi się burzy']));
        $registration = ChipRegistration::sole();
        $registration->update(['mod_status' => 'approved']);

        return $registration;
    }

    public function test_search_returns_approved_registration_without_contact_data(): void
    {
        $this->approvedRegistration();

        $this->getJson('/baza-chipow/szukaj?chip=616-093-900-123-456')
            ->assertOk()
            ->assertJson([
                'found'  => true,
                'result' => [
                    'chip_number'     => '616093900123456',
                    'animal_name'     => 'Burek',
                    'species'         => 'Pies',
                    'breed'           => 'Beagle',
                    'city'            => 'Katowice',
                    'additional_info' => 'Boi się burzy',
                ],
            ])
            ->assertJsonMissingPath('result.contact_email')
            ->assertJsonMissingPath('result.contact_phone')
            ->assertDontSee('wlasciciel@example.com');
    }

    public function test_search_ignores_pending_and_partial_numbers(): void
    {
        $this->post('/baza-chipow', $this->payload());

        $this->getJson('/baza-chipow/szukaj?chip=616093900123456')->assertJson(['found' => false]);

        ChipRegistration::sole()->update(['mod_status' => 'approved']);

        // ** Tylko dokładne dopasowanie - bez wyszukiwania po fragmencie
        $this->getJson('/baza-chipow/szukaj?chip=616093900')->assertJson(['found' => false]);
    }

    public function test_phone_is_revealed_only_for_approved_chip_number(): void
    {
        $this->getJson('/baza-chipow/telefon?chip=616093900123456')->assertNotFound();

        $this->approvedRegistration();

        $this->getJson('/baza-chipow/telefon?chip=616093900123456')
            ->assertOk()
            ->assertJsonStructure(['phone']);
    }

    public function test_confirm_email_rejects_wrong_token(): void
    {
        $this->post('/baza-chipow', $this->payload());
        $registration = ChipRegistration::sole();

        $this->get(route('chips.confirm', ['chipRegistration' => $registration, 'token' => 'zly']))
            ->assertForbidden();
    }
}
