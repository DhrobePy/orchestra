<?php

namespace App\Filament\Resources\Purchasing\PurchasePaymentResource\Pages;

use App\Filament\Resources\Purchasing\PurchasePaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchasePayments extends ListRecords
{
    protected static string $resource = PurchasePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
