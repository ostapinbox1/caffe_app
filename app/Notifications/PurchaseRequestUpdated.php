<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;

class PurchaseRequestUpdated extends PurchaseRequestNotification
{
    /**
     * @param  list<string>  $changes  что поменял управляющий
     */
    public function __construct(PurchaseRequest $purchaseRequest, public array $changes)
    {
        parent::__construct($purchaseRequest);
    }

    protected function title(): string
    {
        $request = $this->purchaseRequest;

        return "Заявка №{$request->number}: {$request->status->getLabel()}";
    }

    protected function body(): string
    {
        return implode("\n", $this->changes);
    }

    protected function color(): string
    {
        return $this->purchaseRequest->status->badgeColor();
    }
}
