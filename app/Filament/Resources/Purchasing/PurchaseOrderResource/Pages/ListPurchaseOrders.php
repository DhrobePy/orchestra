<?php

namespace App\Filament\Resources\Purchasing\PurchaseOrderResource\Pages;

use App\Filament\Resources\Purchasing\PurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
