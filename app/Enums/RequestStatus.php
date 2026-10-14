<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Accepted = 'accepted';
    case Ordered = 'ordered';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Accepted => 'Принята',
            self::Ordered => 'Заказано',
            self::Received => 'Получено',
            self::Cancelled => 'Отменена',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Accepted => 'warning',
            self::Ordered => 'primary',
            self::Received => 'success',
            self::Cancelled => 'gray',
        };
    }

    /**
     * Цвет значка на странице сотрудника (Flux badge).
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::New => 'sky',
            self::Accepted => 'amber',
            self::Ordered => 'emerald',
            self::Received => 'green',
            self::Cancelled => 'zinc',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Received, self::Cancelled], true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->isOpen()));
    }
}
