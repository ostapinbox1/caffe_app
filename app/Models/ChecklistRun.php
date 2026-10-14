<?php

namespace App\Models;

use App\Enums\ChecklistDeadline;
use App\Enums\ChecklistRunStatus;
use App\Models\Concerns\BelongsToCafe;
use App\Notifications\ChecklistSubmittedWithGaps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Чек-лист сотрудника на смену — копия шаблона на момент открытия смены,
 * чтобы правка шаблона не меняла уже заполненное.
 *
 * @property int $id
 * @property int $cafe_id
 * @property int|null $checklist_template_id
 * @property int $user_id
 * @property int $work_shift_id
 * @property string $title
 * @property ChecklistDeadline $deadline_type
 * @property Carbon|null $due_at
 * @property Carbon|null $completed_at
 * @property string|null $comment
 * @property Carbon|null $overdue_notified_at
 * @property Carbon $created_at
 */
#[Fillable([
    'cafe_id', 'checklist_template_id', 'user_id', 'work_shift_id', 'title', 'deadline_type',
    'due_at', 'completed_at', 'comment', 'overdue_notified_at',
])]
class ChecklistRun extends Model
{
    use BelongsToCafe;

    protected function casts(): array
    {
        return [
            'deadline_type' => ChecklistDeadline::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WorkShift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'work_shift_id');
    }

    /**
     * @return HasMany<ChecklistRunItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistRunItem::class)->orderBy('sort');
    }

    /**
     * Не сданные, у которых срок уже прошёл, а смена ещё идёт.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereHas('shift', fn (Builder $query) => $query->whereNull('ended_at'));
    }

    public function status(): ChecklistRunStatus
    {
        if ($this->completed_at !== null) {
            return $this->checkedCount() < $this->items->count()
                ? ChecklistRunStatus::DoneWithGaps
                : ChecklistRunStatus::Done;
        }

        if ($this->shift->ended_at !== null) {
            return ChecklistRunStatus::NotDone;
        }

        return $this->due_at !== null && $this->due_at->isPast()
            ? ChecklistRunStatus::Overdue
            : ChecklistRunStatus::InProgress;
    }

    public function checkedCount(): int
    {
        return $this->items->whereNotNull('checked_at')->count();
    }

    public function progressPercent(): int
    {
        $total = $this->items->count();

        return $total === 0 ? 0 : (int) round($this->checkedCount() / $total * 100);
    }

    public function deadlineLabel(): string
    {
        return $this->due_at !== null ? 'до '.$this->due_at->format('H:i') : 'до закрытия смены';
    }

    public function canBeFilledBy(User $user): bool
    {
        return $this->user_id === $user->id && $this->completed_at === null && $this->shift->ended_at === null;
    }

    /**
     * Отметить пункт или снять отметку. Сохраняется сразу — управляющий видит ход заполнения.
     */
    public function toggle(int $itemId, User $user): ChecklistRunItem
    {
        abort_unless($this->canBeFilledBy($user), 403);

        $item = $this->items()->findOrFail($itemId);
        $item->update(['checked_at' => $item->checked_at === null ? now() : null]);

        return $item;
    }

    /**
     * Сдать чек-лист. Если что-то не отмечено — нужен комментарий, и управляющему уходит уведомление.
     */
    public function complete(User $user, ?string $comment = null): void
    {
        abort_unless($this->canBeFilledBy($user), 403);

        $comment = filled($comment) ? trim($comment) : null;
        $unchecked = $this->items()->whereNull('checked_at')->count();

        if ($unchecked > 0 && $comment === null) {
            throw ValidationException::withMessages([
                'comment' => 'Не отмечено пунктов: '.$unchecked.'. Напишите, почему.',
            ]);
        }

        $this->update(['completed_at' => now(), 'comment' => $comment]);

        if ($unchecked > 0) {
            Notification::send($this->cafe->managers()->get(), new ChecklistSubmittedWithGaps($this->fresh(['items', 'user'])));
        }
    }
}
