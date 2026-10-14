<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Database\Factories\RequestSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Раздел заявок: Кухня, Бар, Хоз часть.
 *
 * @property int $id
 * @property int $cafe_id
 * @property string $name
 * @property int $sort
 */
#[Fillable(['cafe_id', 'name', 'sort'])]
class RequestSection extends Model
{
    /** @use HasFactory<RequestSectionFactory> */
    use BelongsToCafe, HasFactory;

    /**
     * @return HasMany<CatalogCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CatalogCategory::class)->orderBy('sort')->orderBy('name');
    }

    /**
     * @return HasMany<PurchaseRequest, $this>
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
