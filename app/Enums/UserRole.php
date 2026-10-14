<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Владелец',
            self::Manager => 'Управляющий',
            self::Staff => 'Сотрудник',
        };
    }

    /**
     * Может ли роль обрабатывать заявки и заходить в админку.
     */
    public function canManage(): bool
    {
        return $this !== self::Staff;
    }
}
