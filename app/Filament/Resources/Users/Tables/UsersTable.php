<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Должность')
                    ->placeholder('—'),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('email')
                    ->label('Логин')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Доступ')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Доступ включён')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
