<?php

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalog_category_id' => CatalogCategory::factory(),
            'cafe_id' => fn (array $attributes) => CatalogCategory::withoutGlobalScopes()->find($attributes['catalog_category_id'])->cafe_id,
            'name' => fake()->unique()->word(),
            'unit' => Unit::Kilogram,
            'is_hidden' => false,
            'sort' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_hidden' => true]);
    }
}
