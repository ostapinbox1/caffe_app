<?php

namespace App\Notifications;

use App\Models\ChecklistRun;
use App\Models\WorkShift;

/**
 * Сотрудник закрыл смену, не сдав чек-листы. Управляющему.
 */
class ShiftEndedWithOpenChecklists extends CabinetNotification
{
    public function __construct(public WorkShift $shift) {}

    protected function title(): string
    {
        return $this->shift->user->shortName().' закрыл(а) смену без чек-листов';
    }

    protected function body(): string
    {
        return 'Не сдано: '.$this->shift->checklistRuns
            ->whereNull('completed_at')
            ->map(fn (ChecklistRun $run) => "«{$run->title}» ({$run->checkedCount()}/{$run->items->count()})")
            ->implode(', ');
    }

    protected function url(): string
    {
        return route('team', ['date' => $this->shift->started_at->toDateString()]);
    }

    protected function icon(): string
    {
        return 'exclamation-triangle';
    }

    protected function color(): string
    {
        return 'red';
    }
}
