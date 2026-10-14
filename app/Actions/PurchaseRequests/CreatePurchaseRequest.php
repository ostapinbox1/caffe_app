<?php

namespace App\Actions\PurchaseRequests;

use App\Enums\RequestStatus;
use App\Enums\Unit;
use App\Models\Cafe;
use App\Models\CatalogItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestEvent;
use App\Models\RequestSection;
use App\Models\User;
use App\Notifications\PurchaseRequestCreated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CreatePurchaseRequest
{
    /**
     * Создаёт заявку. Строки из справочника берут название и проверяются
     * на принадлежность разделу; одинаковые позиции с одной единицей складываются.
     *
     * @param  list<array{catalog_item_id?: int|null, name?: string|null, unit: Unit|string, quantity: float|int|string}>  $lines
     * @param  array{needed_by?: string|null, is_urgent?: bool, comment?: string|null}  $details
     */
    public function handle(User $author, RequestSection $section, array $lines, array $details = []): PurchaseRequest
    {
        abort_unless($author->is_active && $section->cafe_id === $author->cafe_id, 403);

        $lines = $this->normalizeLines($section, $lines);

        $request = DB::transaction(function () use ($author, $section, $lines, $details): PurchaseRequest {
            // Номер по порядку внутри кофейни: блокируем строку кофейни, пока считаем.
            Cafe::query()->whereKey($author->cafe_id)->lockForUpdate()->first();

            $number = (int) PurchaseRequest::withoutGlobalScopes()
                ->where('cafe_id', $author->cafe_id)
                ->max('number') + 1;

            $request = PurchaseRequest::create([
                'cafe_id' => $author->cafe_id,
                'number' => $number,
                'request_section_id' => $section->id,
                'author_id' => $author->id,
                'status' => RequestStatus::New,
                'is_urgent' => (bool) ($details['is_urgent'] ?? false),
                'needed_by' => filled($details['needed_by'] ?? null) ? Carbon::parse($details['needed_by']) : null,
                'comment' => filled($details['comment'] ?? null) ? trim($details['comment']) : null,
                'status_changed_at' => now(),
            ]);

            foreach ($lines as $sort => $line) {
                $request->items()->create($line + ['sort' => $sort]);
            }

            $request->events()->create([
                'user_id' => $author->id,
                'type' => PurchaseRequestEvent::CREATED,
                'to_status' => RequestStatus::New->value,
            ]);

            return $request;
        });

        Notification::send($author->cafe->managers()->get(), new PurchaseRequestCreated($request));

        return $request;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array{catalog_item_id: int|null, name: string, unit: Unit, quantity: float}>
     */
    private function normalizeLines(RequestSection $section, array $lines): array
    {
        $itemIds = collect($lines)->pluck('catalog_item_id')->filter()->unique()->all();

        $catalog = CatalogItem::withoutGlobalScopes()
            ->whereIn('id', $itemIds)
            ->where('cafe_id', $section->cafe_id)
            ->whereHas('category', fn ($query) => $query->withoutGlobalScopes()->where('request_section_id', $section->id))
            ->get()
            ->keyBy('id');

        $result = [];

        foreach ($lines as $line) {
            $unit = $line['unit'] instanceof Unit ? $line['unit'] : Unit::tryFrom((string) $line['unit']);
            $quantity = (float) str_replace(',', '.', (string) $line['quantity']);
            $itemId = isset($line['catalog_item_id']) ? (int) $line['catalog_item_id'] : null;

            if ($unit === null || $quantity <= 0) {
                throw ValidationException::withMessages(['lines' => 'Проверьте количество и единицы в строках заявки.']);
            }

            if ($itemId !== null) {
                $item = $catalog->get($itemId);

                if ($item === null) {
                    throw ValidationException::withMessages(['lines' => 'Позиции нет в справочнике этого раздела. Обновите страницу.']);
                }

                $name = $item->name;
            } else {
                $name = trim((string) ($line['name'] ?? ''));

                if ($name === '') {
                    throw ValidationException::withMessages(['lines' => 'У своей позиции должно быть название.']);
                }
            }

            $key = ($itemId ?? 'custom:'.mb_strtolower($name)).'|'.$unit->value;

            if (isset($result[$key])) {
                $result[$key]['quantity'] += $quantity;
            } else {
                $result[$key] = ['catalog_item_id' => $itemId, 'name' => $name, 'unit' => $unit, 'quantity' => $quantity];
            }
        }

        if ($result === []) {
            throw ValidationException::withMessages(['lines' => 'Добавьте хотя бы одну позицию.']);
        }

        return array_values($result);
    }
}
