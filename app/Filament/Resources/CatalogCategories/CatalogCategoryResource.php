<?php

namespace App\Filament\Resources\CatalogCategories;

use App\Filament\Resources\CatalogCategories\Pages\ManageCatalogCategories;
use App\Models\CatalogCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CatalogCategoryResource extends Resource
{
    protected static ?string $model = CatalogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Справочник';

    protected static ?string $navigationLabel = 'Категории';

    protected static ?string $modelLabel = 'категория';

    protected static ?string $pluralModelLabel = 'категории';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('request_section_id')
                    ->label('Раздел')
                    ->relationship('section', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(150),
                TextInput::make('sort')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('section.name')
                    ->label('Раздел'),
                TextColumn::make('items_count')
                    ->label('Позиций')
                    ->counts('items'),
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('request_section_id')
                    ->label('Раздел')
                    ->relationship('section', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (CatalogCategory $record): bool => $record->items()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCatalogCategories::route('/'),
        ];
    }
}
