<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Сводка на главной админки: что ждёт управляющего прямо сейчас.
 */
class RequestsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $counts = PurchaseRequest::query()
            ->open()
            ->selectRaw('status, count(*) as total, sum(case when is_urgent then 1 else 0 end) as urgent')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (PurchaseRequest $row) => $row->status->value);

        $count = fn (RequestStatus $status): int => (int) ($counts[$status->value]->total ?? 0);
        $urgent = (int) $counts->sum('urgent');
        $listUrl = PurchaseRequestResource::getUrl('index');

        return [
            Stat::make('Новые заявки', $count(RequestStatus::New))
                ->description('ждут ответа')
                ->color('info')
                ->url($listUrl),
            Stat::make('В работе', $count(RequestStatus::Accepted) + $count(RequestStatus::Ordered))
                ->description('приняты или заказаны')
                ->url($listUrl),
            Stat::make('Срочные', $urgent)
                ->description('среди открытых')
                ->color($urgent > 0 ? 'danger' : 'gray')
                ->url($listUrl),
        ];
    }
}
