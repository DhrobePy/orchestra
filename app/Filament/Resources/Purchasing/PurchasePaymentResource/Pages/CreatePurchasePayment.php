<?php

namespace App\Filament\Resources\Purchasing\PurchasePaymentResource\Pages;

use App\Filament\Resources\Purchasing\PurchasePaymentResource;
use App\Services\ProcurementService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchasePayment extends CreateRecord
{
    protected static string $resource = PurchasePaymentResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(ProcurementService::class)->recordPayment($data);
    }
}
