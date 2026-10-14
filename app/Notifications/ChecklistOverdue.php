<?php

namespace App\Notifications;

use App\Models\ChecklistRun;

/**
 * Срок чек-листа прошёл, а он не сдан. Сотруднику и управляющему.
 */
class ChecklistOverdue extends CabinetNotification
{
    public function __construct(public ChecklistRun $run) {}

    protected function title(): string
    {
        return "Просрочен чек-лист «{$this->run->title}»";
    }

    protected function body(): string
    {
        return $this->run->user->shortName().': отмечено '.$this->run->checkedCount().' из '.$this->run->items->count()
            .', срок был '.$this->run->deadlineLabel().'.';
    }

    protected function url(): string
    {
        return route('checklists.show', $this->run);
    }

    protected function icon(): string
    {
        return 'clock';
    }

    protected function color(): string
    {
        return 'red';
    }
}
