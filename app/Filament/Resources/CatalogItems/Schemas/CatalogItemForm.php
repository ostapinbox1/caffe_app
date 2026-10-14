<?php

namespace App\Filament\Resources\CatalogItems\Schemas;

use App\Enums\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CatalogItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cafe_id')
                    ->relationship('cafe', 'name')
                    ->required(),
                TextInput::make('catalog_category_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                Select::make('unit')
                    ->options(Unit::class)
                    ->required(),
                Toggle::make('is_hidden')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
