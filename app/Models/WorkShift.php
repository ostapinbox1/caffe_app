<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Рабочая смена сотрудника: от «Начать смену» до «Закончить смену».
 *
 * @property int $id
 * @property int $cafe_id
 * @property int $user_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 */
#[Fillable(['cafe_id', 'user_id', 'started_at', 'ended_at'])]
class WorkShift extends Model
{
    use BelongsToCafe;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ChecklistRun, $this>
     */
    public function checklistRuns(): HasMany
    {
        return $this->hasMany(ChecklistRun::class)->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * «3 ч 20 мин».
     */
    public function durationLabel(): string
    {
        $minutes = (int) $this->started_at->diffInMinutes($this->ended_at ?? now());

        return ($minutes >= 60 ? intdiv($minutes, 60).' ч ' : '').($minutes % 60).' мин';
    }
}
