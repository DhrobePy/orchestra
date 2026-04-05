<?php

namespace App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;

use App\Filament\Resources\Procurement\PurchaseOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->isEditable()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Re-calculate total_order_value on edit
        if (!empty($data['quantity']) && !empty($data['unit_price'])) {
            $data['total_order_value'] = round(
                (float) $data['quantity'] * (float) $data['unit_price'],
                2
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return PurchaseOrderResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
