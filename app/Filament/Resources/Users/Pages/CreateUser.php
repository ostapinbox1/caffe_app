<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Человек попадает в кофейню управляющего; почту не подтверждаем —
     * это просто логин, который выдал управляющий.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + [
            'cafe_id' => Auth::user()->cafe_id,
            'email_verified_at' => now(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
