<?php

namespace App\Notifications;

use App\Models\ChecklistRun;

/**
 * Чек-лист сдан, но не всё отмечено. Управляющему.
 */
class ChecklistSubmittedWithGaps extends CabinetNotification
{
    public function __construct(public ChecklistRun $run) {}

    protected function title(): string
    {
        return "«{$this->run->title}» сдан с пропусками";
    }

    protected function body(): string
    {
        return $this->run->user->shortName().': отмечено '.$this->run->checkedCount().' из '.$this->run->items->count()
            .'. Комментарий: '.$this->run->comment;
    }

    protected function url(): string
    {
        return route('checklists.show', $this->run);
    }

    protected function icon(): string
    {
        return 'clipboard-document-check';
    }

    protected function color(): string
    {
        return 'amber';
    }
}
