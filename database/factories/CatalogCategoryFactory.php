<?php

namespace Database\Factories;

use App\Models\CatalogCategory;
use App\Models\RequestSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogCategory>
 */
class CatalogCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_section_id' => RequestSection::factory(),
            'cafe_id' => fn (array $attributes) => RequestSection::withoutGlobalScopes()->find($attributes['request_section_id'])->cafe_id,
            'name' => fake()->unique()->word(),
            'sort' => 0,
        ];
    }
}
