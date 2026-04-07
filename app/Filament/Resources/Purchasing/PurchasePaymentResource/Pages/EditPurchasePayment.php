<?php

namespace App\Filament\Resources\Purchasing\PurchasePaymentResource\Pages;

use App\Filament\Resources\Purchasing\PurchasePaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchasePayment extends EditRecord
{
    protected static string $resource = PurchasePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
