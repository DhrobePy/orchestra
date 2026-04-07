<?php

namespace App\Filament\Resources\Purchasing\SupplierResource\Pages;

use App\Filament\Resources\Purchasing\SupplierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
