<?php
namespace App\Filament\Resources\Sales\CustomerPaymentResource\Pages;

use App\Filament\Resources\Sales\CustomerPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPayments extends ListRecords
{
    protected static string $resource = CustomerPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
