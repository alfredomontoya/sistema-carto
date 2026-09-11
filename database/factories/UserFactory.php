<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\UserService;
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
        $username = fake()->unique()->userName();

        return [
            'name' => fake()->name(),
            'email' => UserService::emailFor($username),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->optional()->numerify('9########'),
            'address' => fake()->optional()->address(),
            'avatar_kind' => 'gallery',
            'avatar_value' => fake()->optional()->randomElement(array_keys(config('avatars.gallery'))),
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
            'recovery_email' => fake()->boolean(60) ? "recuperacion-{$username}@example.com" : null,
            'recovery_email_verified_at' => null,
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
}
