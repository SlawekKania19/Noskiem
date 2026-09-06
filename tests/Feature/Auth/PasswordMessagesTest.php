<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ---------------------------
// Komunikaty walidacji hasła powinny być po polsku (lang/pl/validation.php).
// ---------------------------

class PasswordMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_weak_password_on_profile_update_is_reported_in_polish(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'slabe',
                'password_confirmation' => 'slabe',
            ]);

        $response->assertSessionHasErrorsIn('updatePassword', [
            'password' => 'Hasło musi zawierać przynajmniej jedną wielką i jedną małą literę.',
        ]);
    }

    public function test_weak_password_on_registration_is_reported_in_polish(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Osoba',
            'email' => 'test.osoba@example.test',
            'password' => 'slabe',
            'password_confirmation' => 'slabe',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'Hasło musi zawierać przynajmniej jedną cyfrę.',
        ]);
    }

    public function test_generic_validation_message_is_in_polish(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'nie-email',
            'password' => 'x',
            'password_confirmation' => 'y',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Pole imię i nazwisko jest wymagane.',
            'email' => 'Pole adres e-mail musi być prawidłowym adresem e-mail.',
        ]);
    }
}
