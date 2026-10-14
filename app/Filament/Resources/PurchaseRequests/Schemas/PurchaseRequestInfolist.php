<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\PurchaseRequestEvent;
use App\Models\PurchaseRequestItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Заявка')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('section.name')->label('Раздел'),
                        TextEntry::make('status')->label('Статус')->badge(),
                        TextEntry::make('author.name')->label('Автор'),
                        TextEntry::make('created_at')->label('Создана')->dateTime('d.m.Y H:i'),
                        TextEntry::make('needed_by')->label('Нужно к')->date('d.m.Y')->placeholder('—'),
                        TextEntry::make('is_urgent')
                            ->label('Срочно')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Да' : 'Нет')
                            ->color(fn (bool $state): ?string => $state ? 'danger' : null),
                        TextEntry::make('comment')->label('Комментарий автора')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Ответ управляющего')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('expected_at')->label('Ожидаемая дата поставки')->date('d.m.Y')->placeholder('—'),
                        TextEntry::make('manager_comment')->label('Ответ для кофейни')->placeholder('—'),
                    ]),
                Section::make('Состав')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Позиция'),
                                TableColumn::make('Количество'),
                            ])
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('quantity')
                                    ->state(fn (PurchaseRequestItem $record): string => $record->formattedQuantity().' '.$record->unit->getLabel()),
                            ]),
                    ]),
                Section::make('История')
                    ->columnSpan(1)
                    ->schema([
                        RepeatableEntry::make('events')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('description')
                                    ->hiddenLabel()
                                    ->state(fn (PurchaseRequestEvent $record): string => $record->description())
                                    ->helperText(fn (PurchaseRequestEvent $record): string => $record->created_at->format('d.m.Y H:i').' · '.($record->user?->name ?? '—')),
                            ]),
                    ]),
            ]);
    }
}
