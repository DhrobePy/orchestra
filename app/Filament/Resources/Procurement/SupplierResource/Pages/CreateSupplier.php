<?php

namespace App\Filament\Resources\Procurement\SupplierResource\Pages;

use App\Filament\Resources\Procurement\SupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
