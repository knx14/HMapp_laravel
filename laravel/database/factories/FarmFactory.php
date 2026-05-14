<?php

namespace Database\Factories;

use App\Models\AppUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'app_user_id' => AppUser::factory(),
            'farm_name' => $this->faker->word() . '圃場',
            'cultivation_method' => $this->faker->optional()->word(),
            'crop_type' => $this->faker->optional()->word(),
            'boundary_polygon' => [
                ['lat' => 35.0000, 'lng' => 139.0000],
                ['lat' => 35.0001, 'lng' => 139.0000],
                ['lat' => 35.0001, 'lng' => 139.0001],
                ['lat' => 35.0000, 'lng' => 139.0001],
            ],
        ];
    }
}
