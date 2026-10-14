<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\RequestStatus;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->moveTo('accept', RequestStatus::New, RequestStatus::Accepted, 'Принять'),
            $this->moveTo('order', RequestStatus::Accepted, RequestStatus::Ordered, 'Заказано'),
            $this->moveTo('receive', RequestStatus::Ordered, RequestStatus::Received, 'Получено'),
            EditAction::make()->label('Обработать'),
        ];
    }

    /**
     * Кнопка «на следующий шаг» — чтобы с телефона хватало одного нажатия.
     */
    private function moveTo(string $name, RequestStatus $from, RequestStatus $to, string $label): Action
    {
        return Action::make($name)
            ->label($label)
            ->color('primary')
            ->visible(fn (PurchaseRequest $record): bool => $record->status === $from)
            ->action(function (PurchaseRequest $record) use ($to): void {
                $record->updateByManager(Auth::user(), ['status' => $to]);

                Notification::make()->success()->title("{$record->title()}: {$to->getLabel()}")->send();
            });
    }
}
