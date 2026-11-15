<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ---------------------------
// Otwarta rejestracja kont panelu jest wyłączona - /register kieruje na stronę
// Agentów noskiem (rejestracja agenta: tests/Feature/AgentsTest.php).
// ---------------------------
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_redirects_to_agents_page(): void
    {
        $this->get('/register')->assertRedirect('/agenci');
    }

    public function test_panel_account_cannot_be_created_by_post(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
