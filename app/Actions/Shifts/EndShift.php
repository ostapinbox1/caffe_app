<?php

namespace App\Actions\Shifts;

use App\Models\User;
use App\Models\WorkShift;
use App\Notifications\ShiftEndedWithOpenChecklists;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class EndShift
{
    /**
     * Закрывает смену. Несданные чек-листы остаются «не сдан», управляющему — уведомление.
     */
    public function handle(User $user): WorkShift
    {
        $shift = $user->shifts()->open()->latest('started_at')->first();

        if ($shift === null) {
            throw ValidationException::withMessages(['shift' => 'Смена не открыта.']);
        }

        $shift->update(['ended_at' => now()]);
        $shift->load(['user', 'checklistRuns.items']);

        if ($shift->checklistRuns->whereNull('completed_at')->isNotEmpty()) {
            Notification::send($user->cafe->managers()->whereKeyNot($user->id)->get(), new ShiftEndedWithOpenChecklists($shift));
        }

        return $shift;
    }
}
