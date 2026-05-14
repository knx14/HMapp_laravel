<?php

namespace Database\Factories;

use App\Enums\WorkType;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'work_type' => $this->faker->randomElement(WorkType::cases())->value,
            'work_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'title' => $this->faker->sentence(3),
            'detail' => $this->faker->optional()->paragraph(),
            'amount_value' => $this->faker->optional()->randomFloat(1, 1, 100),
            'amount_unit' => $this->faker->optional()->randomElement(['kg', 'L', 'cm']),
            'scope' => 'whole',
        ];
    }
}
