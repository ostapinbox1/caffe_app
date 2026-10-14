<?php

namespace App\Actions\Shifts;

use App\Enums\ChecklistDeadline;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\ChecklistTemplateSection;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartShift
{
    /**
     * Открывает смену и выдаёт сотруднику его чек-листы: по должности и личные.
     * Чек-лист копируется, чтобы правка шаблона не меняла уже начатое.
     */
    public function handle(User $user): WorkShift
    {
        abort_unless($user->is_active && $user->cafe_id !== null, 403);

        return DB::transaction(function () use ($user): WorkShift {
            // Блокируем строку сотрудника, чтобы двойное нажатие не открыло две смены.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->shifts()->open()->exists()) {
                throw ValidationException::withMessages(['shift' => 'Смена уже открыта.']);
            }

            $shift = $user->shifts()->create(['cafe_id' => $user->cafe_id, 'started_at' => now()]);

            $templates = ChecklistTemplate::withoutGlobalScopes()
                ->where('cafe_id', $user->cafe_id)
                ->assignedTo($user)
                ->with('sections.items')
                // Сначала те, что к времени (утренние), потом «до закрытия смены».
                ->orderBy('sort')
                ->orderByRaw('case when deadline_type = ? then 0 else 1 end', [ChecklistDeadline::Time->value])
                ->orderBy('due_time')
                ->orderBy('name')
                ->get();

            foreach ($templates as $template) {
                $run = $shift->checklistRuns()->create([
                    'cafe_id' => $user->cafe_id,
                    'checklist_template_id' => $template->id,
                    'user_id' => $user->id,
                    'title' => $template->name,
                    'deadline_type' => $template->deadline_type,
                    'due_at' => $template->dueAtFor($shift->started_at),
                ]);

                $run->items()->createMany($template->sections
                    ->flatMap(fn (ChecklistTemplateSection $section) => $section->items->map(fn (ChecklistTemplateItem $item) => [
                        'section' => $section->title,
                        'text' => $item->text,
                        'hint' => $item->hint,
                    ]))
                    ->values()
                    ->map(fn (array $item, int $index) => $item + ['sort' => $index + 1])
                    ->all());
            }

            return $shift;
        });
    }
}
