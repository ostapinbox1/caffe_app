<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'name' => fake()->unique()->jobTitle(),
            'sort' => 0,
        ];
    }
}
