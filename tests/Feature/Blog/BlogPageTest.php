<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts(): void
    {
        $published = Post::factory()->create(['title' => 'Wpis opublikowany']);
        Post::factory()->draft()->create(['title' => 'Wpis roboczy']);
        Post::factory()->scheduled()->create(['title' => 'Wpis zaplanowany']);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Wpis opublikowany')
            ->assertDontSee('Wpis roboczy')
            ->assertDontSee('Wpis zaplanowany');
    }

    public function test_index_can_be_filtered_by_category_query_param(): void
    {
        $catA = BlogCategory::factory()->create(['slug' => 'porady']);
        $catB = BlogCategory::factory()->create(['slug' => 'zdrowie']);

        Post::factory()->create(['title' => 'Artykuł z porad', 'blog_category_id' => $catA->id]);
        Post::factory()->create(['title' => 'Artykuł ze zdrowia', 'blog_category_id' => $catB->id]);

        $this->get('/blog?kategoria=porady')
            ->assertOk()
            ->assertSee('Artykuł z porad')
            ->assertDontSee('Artykuł ze zdrowia');
    }

    public function test_category_page_filters_posts_and_404s_for_unknown_slug(): void
    {
        $category = BlogCategory::factory()->create(['slug' => 'adopcja']);
        Post::factory()->create(['title' => 'W tej kategorii', 'blog_category_id' => $category->id]);
        Post::factory()->create(['title' => 'W innej kategorii']);

        $this->get('/blog/kategoria/adopcja')
            ->assertOk()
            ->assertSee('W tej kategorii')
            ->assertDontSee('W innej kategorii');

        $this->get('/blog/kategoria/nie-istnieje')->assertNotFound();
    }

    public function test_published_post_page_renders_for_guest(): void
    {
        $post = Post::factory()->create(['title' => 'Widoczny wpis']);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('Widoczny wpis');
    }

    public function test_draft_post_returns_404_for_guest(): void
    {
        $post = Post::factory()->draft()->create();

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_scheduled_post_returns_404_for_guest(): void
    {
        $post = Post::factory()->scheduled()->create();

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_draft_post_is_visible_to_admin_and_to_its_author(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->draft()->create([
            'user_id' => $author->id,
            'title' => 'Szkic w podglądzie',
        ]);

        $this->actingAs($author)->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('Szkic w podglądzie')
            ->assertSee('Podgląd');

        $this->actingAs($admin)->get('/blog/'.$post->slug)->assertOk();
    }

    public function test_draft_post_is_not_visible_to_a_different_author(): void
    {
        $author = User::factory()->create(['is_author' => true]);
        $other = User::factory()->create(['is_author' => true]);
        $post = Post::factory()->draft()->create(['user_id' => $author->id]);

        $this->actingAs($other)->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_feed_returns_valid_rss_with_only_published_posts(): void
    {
        Post::factory()->create(['title' => 'W kanale']);
        Post::factory()->draft()->create(['title' => 'Poza kanałem']);

        $response = $this->get('/blog/feed');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $xml = $response->getContent();
        $this->assertStringStartsWith('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<rss version="2.0"', $xml);
        $this->assertStringContainsString('W kanale', $xml);
        $this->assertStringNotContainsString('Poza kanałem', $xml);

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'Kanał RSS powinien być poprawnym XML-em');
    }
}
