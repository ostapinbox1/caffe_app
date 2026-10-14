<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Должность: бариста, повар… К ней привязываются чек-листы.
 *
 * @property int $id
 * @property int $cafe_id
 * @property string $name
 * @property int $sort
 */
#[Fillable(['cafe_id', 'name', 'sort'])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use BelongsToCafe, HasFactory;

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsToMany<ChecklistTemplate, $this>
     */
    public function checklistTemplates(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistTemplate::class);
    }
}
