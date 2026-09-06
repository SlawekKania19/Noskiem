<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            // ** Bierze istniejącego autora, a jak go nie ma — tworzy konto z rolą autora
            'user_id' => User::query()->where('is_author', true)->value('id')
                ?? User::factory()->state(['is_author' => true]),
            'blog_category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->sentences(2, true),
            'body' => collect(fake()->paragraphs(6))
                ->map(fn (string $p) => "<p>{$p}</p>")
                ->implode("\n"),
            'cover_path' => null,
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    // Wpis roboczy — niewidoczny publicznie
    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    // Wpis zaplanowany na przyszłość — opublikowany, ale data jeszcze nie minęła
    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now()->addWeek(),
        ]);
    }
}
