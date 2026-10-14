<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changePassword')
                ->label('Сменить пароль')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->schema([
                    TextInput::make('password')
                        ->label('Новый пароль')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->minLength(8)
                        ->required(),
                ])
                ->action(function (array $data, User $record): void {
                    $record->update(['password' => $data['password']]);

                    // Свой пароль: обновляем отпечаток в сессии, иначе выкинет из системы.
                    if ($record->is(Auth::user())) {
                        session()->put('password_hash_'.Auth::getDefaultDriver(), $record->getAuthPassword());
                    }

                    Notification::make()->success()->title('Пароль изменён. Передайте его сотруднику.')->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
