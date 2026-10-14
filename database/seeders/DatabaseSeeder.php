<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Кофейня и справочник. Людей заводит команда `php artisan cafe:user`.
     */
    public function run(): void
    {
        $cafe = Cafe::query()->firstOrCreate(['name' => 'Кофейня']);

        $this->call(CatalogSeeder::class, parameters: ['cafe' => $cafe]);
    }
}
