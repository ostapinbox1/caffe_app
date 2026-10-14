<?php

namespace App\Filament\Resources\CatalogItems\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CatalogItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('catalog_category_id')->orderBy('sort'))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.section.name')
                    ->label('Раздел'),
                TextColumn::make('category.name')
                    ->label('Категория'),
                TextColumn::make('unit')
                    ->label('Ед.')
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('is_hidden')
                    ->label('Скрыта'),
            ])
            ->filters([
                SelectFilter::make('catalog_category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_hidden')
                    ->label('Скрытые'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->paginated([25, 50, 100]);
    }
}
