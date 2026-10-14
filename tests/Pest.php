<?php

use App\Enums\Unit;
use App\Models\Cafe;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\RequestSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Кофейня с разделом «Кухня», двумя позициями, сотрудником и управляющим.
 *
 * @return object{cafe: Cafe, section: RequestSection, flour: CatalogItem, milk: CatalogItem, staff: User, manager: User}
 */
function kitchen(): object
{
    $cafe = Cafe::factory()->create();
    $section = RequestSection::factory()->create(['cafe_id' => $cafe->id, 'name' => 'Кухня']);
    $category = CatalogCategory::factory()->create(['request_section_id' => $section->id, 'name' => 'Бакалея']);

    return (object) [
        'cafe' => $cafe,
        'section' => $section,
        'flour' => CatalogItem::factory()->create(['catalog_category_id' => $category->id, 'name' => 'Мука', 'unit' => Unit::Kilogram]),
        'milk' => CatalogItem::factory()->create(['catalog_category_id' => $category->id, 'name' => 'Молоко', 'unit' => Unit::Liter]),
        'staff' => User::factory()->create(['cafe_id' => $cafe->id, 'first_name' => 'Анна', 'last_name' => 'Бариста']),
        'manager' => User::factory()->manager()->create(['cafe_id' => $cafe->id, 'first_name' => 'Володя', 'last_name' => 'Управляющий']),
    ];
}
