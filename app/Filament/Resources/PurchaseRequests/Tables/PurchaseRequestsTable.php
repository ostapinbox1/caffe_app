<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('number')
                    ->label('№')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('section.name')
                    ->label('Раздел'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                IconColumn::make('is_urgent')
                    ->label('Срочно')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon(null),
                TextColumn::make('summary')
                    ->label('Состав')
                    ->state(fn (PurchaseRequest $record): string => $record->summary())
                    ->wrap(),
                TextColumn::make('author.name')
                    ->label('Автор')
                    ->searchable(),
                TextColumn::make('needed_by')
                    ->label('Нужно к')
                    ->date('d.m')
                    ->sortable(),
                TextColumn::make('expected_at')
                    ->label('Поставка')
                    ->date('d.m')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(RequestStatus::class)
                    ->multiple()
                    ->default(array_map(fn (RequestStatus $status) => $status->value, RequestStatus::open())),
                SelectFilter::make('request_section_id')
                    ->label('Раздел')
                    ->relationship('section', 'name'),
                TernaryFilter::make('is_urgent')
                    ->label('Срочные'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->label('Обработать'),
            ]);
    }
}
