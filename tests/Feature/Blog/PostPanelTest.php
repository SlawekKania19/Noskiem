<?php

namespace Tests\Feature\Blog;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_sees_only_own_posts_on_the_list(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $other = User::factory()->create(['is_author' => true]);

        Post::factory()->create(['user_id' => $author->id, 'title' => 'Mój wpis autora']);
        Post::factory()->create(['user_id' => $other->id, 'title' => 'Cudzy wpis']);

        $this->actingAs($author)->get('/admin/posts')
            ->assertOk()
            ->assertSee('Mój wpis autora')
            ->assertDontSee('Cudzy wpis');
    }

    public function test_admin_sees_all_posts_on_the_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create(['is_author' => true]);

        Post::factory()->create(['user_id' => $author->id, 'title' => 'Wpis autora']);
        Post::factory()->create(['user_id' => $admin->id, 'title' => 'Wpis admina']);

        $this->actingAs($admin)->get('/admin/posts')
            ->assertOk()
            ->assertSee('Wpis autora')
            ->assertSee('Wpis admina');
    }

    public function test_author_cannot_open_edit_of_a_foreign_post(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $other = User::factory()->create(['is_author' => true]);
        $foreign = Post::factory()->create(['user_id' => $other->id]);

        $this->actingAs($author)->get('/admin/posts/'.$foreign->slug.'/edit')->assertNotFound();
    }

    public function test_author_can_open_edit_of_own_post(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)->get('/admin/posts/'.$post->slug.'/edit')->assertOk();
    }

    public function test_admin_can_open_edit_of_any_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create(['is_author' => true]);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($admin)->get('/admin/posts/'.$post->slug.'/edit')->assertOk();
    }

    public function test_non_staff_user_cannot_view_posts_resource(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(PostResource::canViewAny());
        $this->actingAs($user)->get('/admin/posts')->assertForbidden();
    }

    public function test_author_cannot_reassign_own_post_to_another_author(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $other = User::factory()->create(['is_author' => true]);
        $post = Post::factory()->create(['user_id' => $author->id]);

        Livewire::actingAs($author)
            ->test(EditPost::class, ['record' => $post->slug])
            ->fillForm(['user_id' => $other->id])
            ->call('save');

        $this->assertSame($author->id, $post->refresh()->user_id);
    }
}
