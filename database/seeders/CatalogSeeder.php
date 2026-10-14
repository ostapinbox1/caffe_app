<?php

namespace Database\Seeders;

use App\Enums\Unit;
use App\Models\Cafe;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\RequestSection;
use Illuminate\Database\Seeder;

/**
 * Разделы заявок и справочник позиций из data/catalog.csv
 * (Раздел;Категория;Позиция;Единица). Повторный запуск ничего не дублирует.
 */
class CatalogSeeder extends Seeder
{
    public const SECTIONS = ['Кухня', 'Бар', 'Хоз часть'];

    public function run(Cafe $cafe): void
    {
        $sections = [];

        foreach (self::SECTIONS as $sort => $name) {
            $sections[$name] = RequestSection::withoutGlobalScopes()->firstOrCreate(
                ['cafe_id' => $cafe->id, 'name' => $name],
                ['sort' => $sort + 1],
            );
        }

        $rows = array_map(
            fn (string $line) => str_getcsv($line, ';', '"', ''),
            array_slice(file(__DIR__.'/data/catalog.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), 1),
        );

        $categorySort = [];
        $itemSort = [];

        foreach ($rows as [$sectionName, $categoryName, $itemName, $unitLabel]) {
            $section = $sections[$sectionName] ?? null;

            if ($section === null) {
                continue;
            }

            $category = CatalogCategory::withoutGlobalScopes()->firstOrCreate(
                ['cafe_id' => $cafe->id, 'request_section_id' => $section->id, 'name' => $categoryName],
                ['sort' => $categorySort[$section->id] = ($categorySort[$section->id] ?? 0) + 1],
            );

            CatalogItem::withoutGlobalScopes()->firstOrCreate(
                ['cafe_id' => $cafe->id, 'catalog_category_id' => $category->id, 'name' => $itemName],
                [
                    'unit' => Unit::fromLabel($unitLabel),
                    'sort' => $itemSort[$category->id] = ($itemSort[$category->id] ?? 0) + 1,
                ],
            );
        }
    }
}
