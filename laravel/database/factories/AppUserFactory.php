<?php

namespace Database\Factories;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cognito_sub' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'ja_name' => $this->faker->name(),
            'organization' => 'テスト農協',
        ];
    }

    public function withoutOrganization(): static
    {
        return $this->state(fn () => ['organization' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => AppUser::ROLE_ADMIN,
            'admin_granted_at' => now(),
        ]);
    }
}
