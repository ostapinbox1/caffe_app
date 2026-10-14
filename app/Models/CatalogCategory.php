<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Database\Factories\CatalogCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $cafe_id
 * @property int $request_section_id
 * @property string $name
 * @property int $sort
 */
#[Fillable(['cafe_id', 'request_section_id', 'name', 'sort'])]
class CatalogCategory extends Model
{
    /** @use HasFactory<CatalogCategoryFactory> */
    use BelongsToCafe, HasFactory;

    /**
     * @return BelongsTo<RequestSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(RequestSection::class, 'request_section_id');
    }

    /**
     * @return HasMany<CatalogItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class)->orderBy('sort')->orderBy('name');
    }
}
