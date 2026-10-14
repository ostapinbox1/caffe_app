<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->state(fn (User $record): ?string => $record->avatarUrl())
                    ->circular()
                    ->imageSize(36),
                TextColumn::make('last_name')
                    ->label('ФИО')
                    ->state(fn (User $record): string => $record->fullName())
                    ->searchable(['last_name', 'first_name', 'middle_name'])
                    ->sortable(),
                TextColumn::make('position.name')
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
                SelectFilter::make('position_id')
                    ->label('Должность')
                    ->relationship('position', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Доступ включён')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
