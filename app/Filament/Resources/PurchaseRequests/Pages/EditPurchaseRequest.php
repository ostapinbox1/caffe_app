<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * Через модель: история и уведомление автору.
     *
     * @param  PurchaseRequest  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->updateByManager(Auth::user(), $data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
