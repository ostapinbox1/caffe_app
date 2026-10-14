<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public const POSITIONS = ['Управляющий', 'Бариста', 'Повар', 'Посудомойщик', 'Прачка'];

    /**
     * Кофейня, должности, справочник и чек-листы. Людей заводит команда `php artisan cafe:user`.
     */
    public function run(): void
    {
        $cafe = Cafe::query()->firstOrCreate(
            ['name' => 'Кофейный терминал'],
            ['address' => '65 лет Победы, 1'],
        );

        foreach (self::POSITIONS as $sort => $name) {
            Position::withoutGlobalScopes()->firstOrCreate(['cafe_id' => $cafe->id, 'name' => $name], ['sort' => $sort + 1]);
        }

        $this->call(CatalogSeeder::class, parameters: ['cafe' => $cafe]);
        $this->call(ChecklistSeeder::class, parameters: ['cafe' => $cafe]);
    }
}
