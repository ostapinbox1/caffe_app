<?php

namespace App\Filament\Resources\CatalogItems;

use App\Filament\Resources\CatalogItems\Pages\CreateCatalogItem;
use App\Filament\Resources\CatalogItems\Pages\EditCatalogItem;
use App\Filament\Resources\CatalogItems\Pages\ListCatalogItems;
use App\Filament\Resources\CatalogItems\Schemas\CatalogItemForm;
use App\Filament\Resources\CatalogItems\Tables\CatalogItemsTable;
use App\Models\CatalogItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CatalogItemResource extends Resource
{
    protected static ?string $model = CatalogItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Справочник';

    protected static ?string $navigationLabel = 'Позиции';

    protected static ?string $modelLabel = 'позиция';

    protected static ?string $pluralModelLabel = 'позиции';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return CatalogItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogItemsTable::configure($table);
    }

    /**
     * @return Builder<CatalogItem>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category.section');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogItems::route('/'),
            'create' => CreateCatalogItem::route('/create'),
            'edit' => EditCatalogItem::route('/{record}/edit'),
        ];
    }
}
