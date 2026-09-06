<?php

namespace Tests\Feature\Blog;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthorProfilePanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_profile_section_is_hidden_until_is_author_is_enabled(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertFormFieldIsHidden('slug')
            ->assertFormFieldIsHidden('bio')
            ->fillForm(['is_author' => true])
            ->assertFormFieldIsVisible('slug')
            ->assertFormFieldIsVisible('bio')
            ->assertFormFieldIsVisible('signature')
            ->assertFormFieldIsVisible('linkedin_url');
    }

    public function test_creating_an_author_without_a_slug_auto_generates_one(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Maria Nowak',
                'email' => 'maria@example.test',
                'password' => 'Password123',
                'is_author' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('maria-nowak', User::where('email', 'maria@example.test')->value('slug'));
    }

    public function test_admin_can_edit_an_author_profile(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->author()->create();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $author->id])
            ->fillForm([
                'headline' => 'Behawiorystka',
                'website_url' => 'https://przyklad.test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $author->refresh();
        $this->assertSame('Behawiorystka', $author->headline);
        $this->assertSame('https://przyklad.test', $author->website_url);
    }
}
