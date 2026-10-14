<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя и фамилия')
                    ->required()
                    ->maxLength(255),
                TextInput::make('position')
                    ->label('Должность')
                    ->placeholder('Бариста, повар…')
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Логин (почта)')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Оставьте пустым, чтобы не менять.' : null),
                Select::make('role')
                    ->label('Роль')
                    ->options(fn (): array => self::roleOptions())
                    ->default(UserRole::Staff->value)
                    ->required()
                    // Себе роль не меняем — чтобы случайно не потерять доступ к админке.
                    ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false),
                Toggle::make('is_active')
                    ->label('Доступ включён')
                    ->helperText('Выключите, когда человек уволился: войти он больше не сможет, его заявки останутся.')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false),
            ]);
    }

    /**
     * Назначить владельца может только владелец.
     *
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        $roles = Auth::user()?->role === UserRole::Owner
            ? UserRole::cases()
            : [UserRole::Manager, UserRole::Staff];

        return collect($roles)->mapWithKeys(fn (UserRole $role) => [$role->value => $role->getLabel()])->all();
    }
}
