<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Konto autora bloga z wypełnionym profilem (slug dolicza się w modelu).
     */
    public function author(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_author' => true,
            'headline' => fake()->sentence(4),
            'bio' => '<p>'.fake()->paragraph().'</p>',
            'signature' => '<p>'.fake()->sentence().'</p>',
            'website_url' => 'https://'.fake()->domainName(),
        ]);
    }
}
