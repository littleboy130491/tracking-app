<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
            'company_name' => null,
            'company_address' => null,
            'pic_name' => null,
            'pic_phone' => null,
            'last_login_at' => null,
            'is_active' => true,
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

    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);

            $user->assignRole($role);
        });
    }

    public function admin(): static
    {
        return $this->withRole(User::ROLE_ADMIN);
    }

    public function customer(): static
    {
        return $this->withRole(User::ROLE_CUSTOMER)->state(function (array $attributes): array {
            $companyName = $attributes['company_name'] ?? fake()->company();

            return [
                'company_name' => $companyName,
                'name' => $attributes['name'] ?? $companyName,
            ];
        });
    }
}
