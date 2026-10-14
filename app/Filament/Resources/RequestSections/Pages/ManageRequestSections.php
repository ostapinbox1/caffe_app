<?php

namespace App\Filament\Resources\RequestSections\Pages;

use App\Filament\Resources\RequestSections\RequestSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRequestSections extends ManageRecords
{
    protected static string $resource = RequestSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
