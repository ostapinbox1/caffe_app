<?php

namespace App\Models;

use App\Enums\Unit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Строка заявки. Название и единица копируются из справочника,
 * чтобы правка справочника не меняла старые заявки.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property int|null $catalog_item_id
 * @property string $name
 * @property Unit $unit
 * @property string $quantity
 * @property int $sort
 */
#[Fillable(['purchase_request_id', 'catalog_item_id', 'name', 'unit', 'quantity', 'sort'])]
class PurchaseRequestItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'unit' => Unit::class,
            'quantity' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * «1,5» вместо «1.500».
     */
    public function formattedQuantity(): string
    {
        return self::formatQuantity((float) $this->quantity);
    }

    public function label(): string
    {
        return "{$this->name} {$this->formattedQuantity()} {$this->unit->getLabel()}";
    }

    public static function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, ',', ' '), '0'), ',');
    }
}
