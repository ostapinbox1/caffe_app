<?php

namespace App\Notifications;

class PurchaseRequestCreated extends PurchaseRequestNotification
{
    protected function title(): string
    {
        $request = $this->purchaseRequest;
        $urgent = $request->is_urgent ? 'Срочно! ' : '';

        return "{$urgent}Новая заявка №{$request->number} · {$request->section->name}";
    }

    protected function body(): string
    {
        $request = $this->purchaseRequest;

        return $request->author->shortName().': '.$request->summary();
    }

    protected function color(): string
    {
        return $this->purchaseRequest->is_urgent ? 'red' : 'sky';
    }
}
