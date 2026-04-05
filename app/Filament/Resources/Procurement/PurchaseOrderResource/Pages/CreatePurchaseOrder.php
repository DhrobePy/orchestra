<?php

namespace App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;

use App\Filament\Resources\Procurement\PurchaseOrderResource;
use App\Services\ProcurementService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Delegate all PO creation logic to ProcurementService
        $service = app(ProcurementService::class);
        $po = $service->createPurchaseOrder($data);

        // Halt the default create flow by redirecting to the view page
        $this->redirect(PurchaseOrderResource::getUrl('view', ['record' => $po->id]));

        // Return data that won't be used (redirect above exits the lifecycle)
        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // If we reach here (no redirect happened), use default logic
        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return PurchaseOrderResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
