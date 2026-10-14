<?php

namespace App\Enums;

/**
 * Состояние чек-листа сотрудника. Не хранится — считается по датам.
 */
enum ChecklistRunStatus: string
{
    case InProgress = 'in_progress';
    case Overdue = 'overdue';
    case Done = 'done';
    case DoneWithGaps = 'done_with_gaps';
    case NotDone = 'not_done';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'В процессе',
            self::Overdue => 'Просрочен',
            self::Done => 'Сдан',
            self::DoneWithGaps => 'Сдан с пропусками',
            self::NotDone => 'Не сдан',
        };
    }

    /**
     * Цвет значка Flux.
     */
    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'sky',
            self::Overdue, self::NotDone => 'red',
            self::Done => 'green',
            self::DoneWithGaps => 'amber',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Done, self::DoneWithGaps, self::NotDone], true);
    }
}
