<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('cafe.name')
                    ->label('Cafe'),
                TextEntry::make('number')
                    ->numeric(),
                TextEntry::make('request_section_id')
                    ->numeric(),
                TextEntry::make('author.name')
                    ->label('Author'),
                TextEntry::make('status')
                    ->badge(),
                IconEntry::make('is_urgent')
                    ->boolean(),
                TextEntry::make('needed_by')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('comment')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('manager_comment')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('expected_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status_changed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
