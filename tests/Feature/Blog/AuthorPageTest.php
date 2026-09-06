<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_page_renders_for_an_author_with_a_slug(): void
    {
        $author = User::factory()->author()->create(['name' => 'Anna Testowa']);

        $this->get(route('blog.author', $author))
            ->assertOk()
            ->assertSee('Anna Testowa')
            ->assertSee($author->headline);
    }

    public function test_author_page_404s_for_a_non_author(): void
    {
        $user = User::factory()->create(); // brak is_author

        $this->get('/blog/autor/'.($user->slug ?? 'ktokolwiek'))->assertNotFound();
    }

    public function test_author_page_404s_for_unknown_slug(): void
    {
        $this->get('/blog/autor/nie-ma-takiego')->assertNotFound();
    }

    public function test_author_page_lists_only_that_authors_published_posts(): void
    {
        $author = User::factory()->author()->create();
        $other = User::factory()->author()->create();

        Post::factory()->create(['user_id' => $author->id, 'title' => 'Mój opublikowany']);
        Post::factory()->draft()->create(['user_id' => $author->id, 'title' => 'Mój szkic']);
        Post::factory()->create(['user_id' => $other->id, 'title' => 'Cudzy wpis']);

        $this->get(route('blog.author', $author))
            ->assertOk()
            ->assertSee('Mój opublikowany')
            ->assertDontSee('Mój szkic')
            ->assertDontSee('Cudzy wpis');
    }

    public function test_author_page_shows_a_correctly_pluralised_article_count(): void
    {
        $author = User::factory()->author()->create();
        Post::factory()->count(3)->create(['user_id' => $author->id]);

        $this->get(route('blog.author', $author))
            ->assertOk()
            ->assertSee('3 opublikowane artykuły');

        $solo = User::factory()->author()->create();
        Post::factory()->create(['user_id' => $solo->id]);

        $this->get(route('blog.author', $solo))
            ->assertOk()
            ->assertSee('1 opublikowany artykuł');
    }

    public function test_author_signature_shows_on_the_author_page_when_set(): void
    {
        $withSig = User::factory()->author()->create(['signature' => '<p>Napisz do mnie na kontakt</p>']);
        $this->get(route('blog.author', $withSig))->assertOk()->assertSee('Napisz do mnie na kontakt');

        $noSig = User::factory()->author()->create(['signature' => null]);
        $this->get(route('blog.author', $noSig))->assertOk()->assertDontSee('bg-[#f8f8f4]', false);
    }

    public function test_social_links_render_only_for_filled_platforms(): void
    {
        $author = User::factory()->author()->create([
            'website_url' => null,
            'facebook_url' => 'https://facebook.com/anna',
            'instagram_url' => null,
        ]);

        $this->get(route('blog.author', $author))
            ->assertOk()
            ->assertSee('https://facebook.com/anna');
    }

    public function test_post_page_shows_the_author_badge_linking_to_the_profile(): void
    {
        $author = User::factory()->author()->create(['name' => 'Jan Autor']);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('Jan Autor')
            ->assertSee(route('blog.author', $author), false);
    }

    public function test_post_page_shows_author_signature_block_only_when_set(): void
    {
        $author = User::factory()->author()->create(['signature' => '<p>Stopka pod artykułem</p>']);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->get('/blog/'.$post->slug)->assertOk()->assertSee('Stopka pod artykułem');

        $author->update(['signature' => null]);
        $this->get('/blog/'.$post->slug)->assertOk()->assertDontSee('Stopka pod artykułem');
    }

    public function test_blog_list_card_links_author_name_to_the_profile(): void
    {
        $author = User::factory()->author()->create(['name' => 'Kartowy Autor']);
        Post::factory()->create(['user_id' => $author->id]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee(route('blog.author', $author), false);
    }
}
