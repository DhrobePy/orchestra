<?php

namespace App\Filament\Resources\Purchasing\PurchaseOrderResource\Pages;

use App\Filament\Resources\Purchasing\PurchaseOrderResource;
use App\Services\ProcurementService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(ProcurementService::class)->createPurchaseOrder($data);
    }
}
