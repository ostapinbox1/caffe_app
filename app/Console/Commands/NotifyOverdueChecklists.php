<?php

namespace App\Console\Commands;

use App\Models\ChecklistRun;
use App\Notifications\ChecklistOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Раз в 5 минут (планировщик): просроченные чек-листы — сотруднику и управляющим, один раз.
 */
class NotifyOverdueChecklists extends Command
{
    protected $signature = 'checklists:notify-overdue';

    protected $description = 'Уведомить о просроченных чек-листах';

    public function handle(): int
    {
        $runs = ChecklistRun::withoutGlobalScopes()
            ->overdue()
            ->whereNull('overdue_notified_at')
            ->with(['items', 'user', 'cafe'])
            ->get();

        foreach ($runs as $run) {
            $recipients = $run->cafe->managers()->get()->push($run->user)->unique('id');

            Notification::send($recipients, new ChecklistOverdue($run));
            $run->update(['overdue_notified_at' => now()]);
        }

        $this->info("Уведомлений о просрочке: {$runs->count()}");

        return self::SUCCESS;
    }
}
