<?php

namespace App\Notifications;

class PurchaseRequestCancelled extends PurchaseRequestNotification
{
    protected function title(): string
    {
        return "Заявка №{$this->purchaseRequest->number} отменена автором";
    }

    protected function body(): string
    {
        $request = $this->purchaseRequest;

        return $request->author->shortName().': '.$request->summary();
    }
}
