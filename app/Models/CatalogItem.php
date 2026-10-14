<?php

namespace App\Models;

use App\Enums\Unit;
use App\Models\Concerns\BelongsToCafe;
use Database\Factories\CatalogItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Позиция справочника: «Мука, кг».
 *
 * @property int $id
 * @property int $cafe_id
 * @property int $catalog_category_id
 * @property string $name
 * @property Unit $unit
 * @property bool $is_hidden
 * @property int $sort
 */
#[Fillable(['cafe_id', 'catalog_category_id', 'name', 'unit', 'is_hidden', 'sort'])]
class CatalogItem extends Model
{
    /** @use HasFactory<CatalogItemFactory> */
    use BelongsToCafe, HasFactory;

    protected function casts(): array
    {
        return [
            'unit' => Unit::class,
            'is_hidden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CatalogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'catalog_category_id');
    }

    /**
     * Позиции, которые показываются в форме заявки.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }
}
