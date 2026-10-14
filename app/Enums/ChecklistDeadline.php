<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * К какому сроку сдать чек-лист.
 */
enum ChecklistDeadline: string implements HasLabel
{
    case Time = 'time';
    case ShiftEnd = 'shift_end';

    public function getLabel(): string
    {
        return match ($this) {
            self::Time => 'К определённому времени',
            self::ShiftEnd => 'До закрытия смены',
        };
    }
}
