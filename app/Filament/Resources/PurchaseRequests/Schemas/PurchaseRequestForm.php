<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\RequestStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

/**
 * Что меняет управляющий. Состав заявки не правится: это слова автора.
 */
class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Статус')
                    ->options(RequestStatus::class)
                    ->required()
                    ->native(false),
                DatePicker::make('expected_at')
                    ->label('Ожидаемая дата поставки')
                    ->native(false)
                    ->displayFormat('d.m.Y'),
                Textarea::make('manager_comment')
                    ->label('Ответ для кофейни')
                    ->helperText('Автор заявки увидит его у себя в кабинете.')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}
