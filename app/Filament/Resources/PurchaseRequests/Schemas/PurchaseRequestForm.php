<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\RequestStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cafe_id')
                    ->relationship('cafe', 'name')
                    ->required(),
                TextInput::make('number')
                    ->required()
                    ->numeric(),
                TextInput::make('request_section_id')
                    ->required()
                    ->numeric(),
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->required(),
                Select::make('status')
                    ->options(RequestStatus::class)
                    ->required(),
                Toggle::make('is_urgent')
                    ->required(),
                DatePicker::make('needed_by'),
                Textarea::make('comment')
                    ->columnSpanFull(),
                Textarea::make('manager_comment')
                    ->columnSpanFull(),
                DatePicker::make('expected_at'),
                DateTimePicker::make('status_changed_at'),
            ]);
    }
}
