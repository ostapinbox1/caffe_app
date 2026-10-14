<?php

namespace App\Models;

use App\Enums\ChecklistDeadline;
use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\CarbonInterface;

/**
 * Шаблон чек-листа из конструктора управляющего.
 * Назначается должностям и/или конкретным сотрудникам.
 *
 * @property int $id
 * @property int $cafe_id
 * @property string $name
 * @property string|null $description
 * @property ChecklistDeadline $deadline_type
 * @property string|null $due_time «10:00» или «10:00:00»
 * @property bool $is_active
 * @property int $sort
 */
#[Fillable(['cafe_id', 'name', 'description', 'deadline_type', 'due_time', 'is_active', 'sort'])]
class ChecklistTemplate extends Model
{
    use BelongsToCafe;

    protected function casts(): array
    {
        return [
            'deadline_type' => ChecklistDeadline::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ChecklistTemplateSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ChecklistTemplateSection::class)->orderBy('sort');
    }

    /**
     * @return HasManyThrough<ChecklistTemplateItem, ChecklistTemplateSection, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(ChecklistTemplateItem::class, ChecklistTemplateSection::class);
    }

    /**
     * @return BelongsToMany<Position, $this>
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany<ChecklistRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ChecklistRun::class);
    }

    /**
     * Активные чек-листы, назначенные сотруднику — через должность или лично.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAssignedTo(Builder $query, User $user): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereHas('users', fn (Builder $query) => $query->whereKey($user->id))
                ->when($user->position_id, fn (Builder $query) => $query
                    ->orWhereHas('positions', fn (Builder $query) => $query->whereKey($user->position_id))));
    }

    /**
     * Срок сдачи для смены, начатой в $shiftStart. Если время уже прошло — час от начала смены.
     */
    public function dueAtFor(CarbonInterface $shiftStart): ?CarbonInterface
    {
        if ($this->deadline_type !== ChecklistDeadline::Time || blank($this->due_time)) {
            return null;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $this->due_time));
        $dueAt = $shiftStart->copy()->setTime($hours, $minutes);

        return $dueAt->lessThanOrEqualTo($shiftStart) ? $shiftStart->copy()->addHour() : $dueAt;
    }

    public function deadlineLabel(): string
    {
        return $this->deadline_type === ChecklistDeadline::Time && filled($this->due_time)
            ? 'до '.substr($this->due_time, 0, 5)
            : 'до закрытия смены';
    }
}
