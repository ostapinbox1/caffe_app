<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;

abstract class PurchaseRequestNotification extends CabinetNotification
{
    public function __construct(public PurchaseRequest $purchaseRequest) {}

    protected function url(): string
    {
        return route('requests.show', $this->purchaseRequest);
    }

    protected function icon(): string
    {
        return 'shopping-cart';
    }
}
