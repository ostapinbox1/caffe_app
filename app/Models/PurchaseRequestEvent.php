<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Запись в истории заявки.
 *
 * @property int $id
 * @property int $purchase_request_id
 * @property int|null $user_id
 * @property string $type
 * @property RequestStatus|null $from_status
 * @property RequestStatus|null $to_status
 * @property string|null $note
 * @property Carbon $created_at
 */
#[Fillable(['purchase_request_id', 'user_id', 'type', 'from_status', 'to_status', 'note'])]
class PurchaseRequestEvent extends Model
{
    public const CREATED = 'created';

    public const STATUS = 'status';

    public const COMMENT = 'comment';

    public const EXPECTED = 'expected';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'from_status' => RequestStatus::class,
            'to_status' => RequestStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function description(): string
    {
        return match ($this->type) {
            self::CREATED => 'Создана',
            self::STATUS => 'Статус: '.$this->to_status?->getLabel().($this->note ? " ({$this->note})" : ''),
            self::COMMENT => 'Ответ управляющего: '.$this->note,
            self::EXPECTED => 'Ожидаемая дата поставки: '.$this->note,
            default => (string) $this->note,
        };
    }
}
