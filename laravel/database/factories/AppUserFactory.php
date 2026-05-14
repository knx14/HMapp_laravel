<?php

namespace Database\Factories;

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
        ];
    }
}
