<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о заявке в базе данных. Формат Filament — чтобы колокольчик
 * админки показывал его сам; страница сотрудника читает title, body и url.
 */
abstract class PurchaseRequestNotification extends Notification
{
    public function __construct(public PurchaseRequest $purchaseRequest) {}

    abstract protected function title(): string;

    abstract protected function body(): string;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $url = $this->url($notifiable);

        return FilamentNotification::make()
            ->title($this->title())
            ->body($this->body())
            ->icon('heroicon-o-shopping-cart')
            ->actions([
                Action::make('open')->label('Открыть')->url($url)->markAsRead(),
            ])
            ->getDatabaseMessage() + ['url' => $url];
    }

    /**
     * Управляющему — карточка в админке, сотруднику — страница заявки.
     */
    protected function url(object $notifiable): string
    {
        if ($notifiable instanceof User && $notifiable->isManager()) {
            return url("/admin/purchase-requests/{$this->purchaseRequest->id}");
        }

        return route('requests.show', $this->purchaseRequest);
    }
}
