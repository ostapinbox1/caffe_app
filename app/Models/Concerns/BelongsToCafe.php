<?php

namespace App\Models\Concerns;

use App\Models\Cafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Запись принадлежит кофейне. Вошедший пользователь видит только записи
 * своей кофейни, новые записи получают его кофейню автоматически.
 */
trait BelongsToCafe
{
    public static function bootBelongsToCafe(): void
    {
        static::addGlobalScope('cafe', function (Builder $query): void {
            $cafeId = Auth::user()?->cafe_id;

            if ($cafeId !== null) {
                $query->where($query->qualifyColumn('cafe_id'), $cafeId);
            }
        });

        static::creating(function (Model $model): void {
            $model->cafe_id ??= Auth::user()?->cafe_id;
        });
    }

    /**
     * @return BelongsTo<Cafe, $this>
     */
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }
}
