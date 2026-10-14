<?php

namespace App\Support;

use App\Enums\RequestStatus;
use App\Models\ChecklistRun;
use App\Models\PurchaseRequest;
use App\Models\User;

/**
 * Числа на значках меню кабинета.
 */
class CabinetBadges
{
    /**
     * @return array{notifications: int, checklists: int, requests: int}
     */
    public static function for(User $user): array
    {
        return [
            'notifications' => $user->unreadNotifications()->count(),
            // Мои несданные чек-листы на открытой смене.
            'checklists' => ChecklistRun::query()
                ->where('user_id', $user->id)
                ->whereNull('completed_at')
                ->whereHas('shift', fn ($query) => $query->whereNull('ended_at'))
                ->count(),
            // Управляющему — новые заявки, которые ждут ответа.
            'requests' => $user->isManager()
                ? PurchaseRequest::query()->where('status', RequestStatus::New)->count()
                : 0,
        ];
    }
}
