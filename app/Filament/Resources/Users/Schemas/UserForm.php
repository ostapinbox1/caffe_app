<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Человек')
                    ->columns(3)
                    ->schema([
                        TextInput::make('last_name')->label('Фамилия')->maxLength(100),
                        TextInput::make('first_name')->label('Имя')->required()->maxLength(100),
                        TextInput::make('middle_name')->label('Отчество')->maxLength(100),
                        Select::make('position_id')
                            ->label('Должность')
                            ->relationship('position', 'name', fn ($query) => $query->orderBy('sort'))
                            ->helperText('От должности зависят чек-листы на смену.')
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Название')->required()->maxLength(100),
                            ]),
                    ]),
                Section::make('Вход и права')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Логин (почта)')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        // Только при создании; потом — кнопкой «Сменить пароль».
                        // new-password — чтобы браузер не подставлял сюда сохранённый пароль.
                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->minLength(8)
                            ->required()
                            ->visibleOn('create'),
                        Select::make('role')
                            ->label('Роль')
                            ->options(fn (): array => self::roleOptions())
                            ->default(UserRole::Staff->value)
                            ->required()
                            ->helperText('Управляющий обрабатывает заявки, видит смены и чек-листы всех и заходит в настройки.')
                            // Себе роль не меняем — чтобы случайно не потерять доступ к настройкам.
                            ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false),
                        Toggle::make('is_active')
                            ->label('Доступ включён')
                            ->helperText('Выключите, когда человек уволился: войти он больше не сможет, его заявки останутся.')
                            ->default(true)
                            ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false),
                    ]),
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
