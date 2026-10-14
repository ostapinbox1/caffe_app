<?php

namespace App\Filament\Resources\CatalogItems\Schemas;

use App\Enums\Unit;
use App\Models\CatalogCategory;
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
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(150),
                Select::make('catalog_category_id')
                    ->label('Категория')
                    ->relationship('category', 'name', fn ($query) => $query->with('section')->orderBy('request_section_id')->orderBy('sort'))
                    ->getOptionLabelFromRecordUsing(fn (CatalogCategory $record): string => "{$record->section->name} · {$record->name}")
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('unit')
                    ->label('Единица по умолчанию')
                    ->options(Unit::class)
                    ->default(Unit::Piece)
                    ->required(),
                TextInput::make('sort')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_hidden')
                    ->label('Скрыть из формы заявки')
                    ->helperText('Позиция не удаляется и остаётся в старых заявках.'),
            ]);
    }
}
