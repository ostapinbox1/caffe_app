<?php

namespace App\Filament\Resources\CatalogItems\Pages;

use App\Filament\Resources\CatalogItems\CatalogItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalogItem extends CreateRecord
{
    protected static string $resource = CatalogItemResource::class;
}
