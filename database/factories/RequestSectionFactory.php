<?php

namespace Database\Factories;

use App\Models\Cafe;
use App\Models\RequestSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestSection>
 */
class RequestSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cafe_id' => Cafe::factory(),
            'name' => fake()->randomElement(['Кухня', 'Бар', 'Хоз часть']),
            'sort' => 0,
        ];
    }
}
