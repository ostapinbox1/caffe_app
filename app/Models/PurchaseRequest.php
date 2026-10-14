<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToCafe;
use App\Notifications\PurchaseRequestCancelled;
use App\Notifications\PurchaseRequestUpdated;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Заявка на закупку.
 *
 * @property int $id
 * @property int $cafe_id
 * @property int $number
 * @property int $request_section_id
 * @property int $author_id
 * @property RequestStatus $status
 * @property bool $is_urgent
 * @property Carbon|null $needed_by
 * @property string|null $comment
 * @property string|null $manager_comment
 * @property Carbon|null $expected_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'cafe_id', 'number', 'request_section_id', 'author_id', 'status', 'is_urgent',
    'needed_by', 'comment', 'manager_comment', 'expected_at', 'status_changed_at',
])]
class PurchaseRequest extends Model
{
    use BelongsToCafe;

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'is_urgent' => 'boolean',
            'needed_by' => 'date',
            'expected_at' => 'date',
            'status_changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RequestSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(RequestSection::class, 'request_section_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return HasMany<PurchaseRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class)->orderBy('sort');
    }

    /**
     * @return HasMany<PurchaseRequestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PurchaseRequestEvent::class)->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', RequestStatus::open());
    }

    public function title(): string
    {
        return "Заявка №{$this->number}";
    }

    /**
     * Состав одной строкой: «Мука 2 кг, Молоко 6 л и ещё 3».
     */
    public function summary(int $limit = 3): string
    {
        $items = $this->items;
        $text = $items->take($limit)->map(fn (PurchaseRequestItem $item) => $item->label())->implode(', ');

        if ($items->count() > $limit) {
            $text .= ' и ещё '.($items->count() - $limit);
        }

        return $text;
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->author_id === $user->id && $this->status === RequestStatus::New;
    }

    /**
     * Автор отменяет свою заявку, пока её не приняли.
     */
    public function cancelBy(User $user): void
    {
        abort_unless($this->canBeCancelledBy($user), 403);

        DB::transaction(function () use ($user): void {
            $this->events()->create([
                'user_id' => $user->id,
                'type' => PurchaseRequestEvent::STATUS,
                'from_status' => $this->status->value,
                'to_status' => RequestStatus::Cancelled->value,
                'note' => 'Отменена автором',
            ]);

            $this->update(['status' => RequestStatus::Cancelled, 'status_changed_at' => now()]);
        });

        Notification::send($this->cafe->managers()->get(), new PurchaseRequestCancelled($this));
    }

    /**
     * Управляющий меняет статус, ответ для кофейни и ожидаемую дату.
     * Каждое изменение пишется в историю, автору уходит одно уведомление.
     *
     * @param  array{status?: RequestStatus|string|null, manager_comment?: string|null, expected_at?: mixed}  $data
     */
    public function updateByManager(User $manager, array $data): void
    {
        $changes = [];

        DB::transaction(function () use ($manager, $data, &$changes): void {
            $status = $data['status'] ?? $this->status;
            $status = $status instanceof RequestStatus ? $status : RequestStatus::from($status);

            if ($status !== $this->status) {
                $this->events()->create([
                    'user_id' => $manager->id,
                    'type' => PurchaseRequestEvent::STATUS,
                    'from_status' => $this->status->value,
                    'to_status' => $status->value,
                ]);
                $changes[] = 'Статус: '.$status->getLabel();
                $this->status = $status;
                $this->status_changed_at = now();
            }

            if (array_key_exists('manager_comment', $data)) {
                $comment = filled($data['manager_comment']) ? trim($data['manager_comment']) : null;

                if ($comment !== $this->manager_comment) {
                    if ($comment !== null) {
                        $this->events()->create([
                            'user_id' => $manager->id,
                            'type' => PurchaseRequestEvent::COMMENT,
                            'note' => $comment,
                        ]);
                        $changes[] = 'Ответ управляющего: '.$comment;
                    }
                    $this->manager_comment = $comment;
                }
            }

            if (array_key_exists('expected_at', $data)) {
                $expected = filled($data['expected_at']) ? Carbon::parse($data['expected_at'])->startOfDay() : null;

                if ($expected?->toDateString() !== $this->expected_at?->toDateString()) {
                    $note = $expected ? $expected->translatedFormat('j F') : 'снята';
                    $this->events()->create([
                        'user_id' => $manager->id,
                        'type' => PurchaseRequestEvent::EXPECTED,
                        'note' => $note,
                    ]);
                    $changes[] = 'Ожидаемая дата поставки: '.$note;
                    $this->expected_at = $expected;
                }
            }

            $this->save();
        });

        if ($changes !== [] && $this->author_id !== $manager->id) {
            $this->author->notify(new PurchaseRequestUpdated($this, $changes));
        }
    }
}
