<?php

namespace Tests\Feature\Blog;

use App\Filament\Pages\EditProfile;
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

    public function test_author_sees_profile_section_on_the_edit_profile_page(): void
    {
        $author = User::factory()->author()->create();

        Livewire::actingAs($author)
            ->test(EditProfile::class)
            ->assertFormFieldIsVisible('headline')
            ->assertFormFieldIsVisible('bio')
            ->assertFormFieldIsVisible('signature');
    }

    public function test_non_author_does_not_see_profile_section_on_the_edit_profile_page(): void
    {
        $moderator = User::factory()->create(['is_moderator' => true]);

        Livewire::actingAs($moderator)
            ->test(EditProfile::class)
            ->assertFormFieldIsHidden('headline')
            ->assertFormFieldIsHidden('bio');
    }

    public function test_author_can_update_own_profile_via_the_edit_profile_page(): void
    {
        $author = User::factory()->author()->create();

        Livewire::actingAs($author)
            ->test(EditProfile::class)
            ->fillForm([
                'headline' => 'Wolontariuszka schroniska',
                'instagram_url' => 'https://instagram.com/mojprofil',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $author->refresh();
        $this->assertSame('Wolontariuszka schroniska', $author->headline);
        $this->assertSame('https://instagram.com/mojprofil', $author->instagram_url);
    }
}
