<?php
namespace App\Filament\Resources\Sales\CustomerPaymentResource\Pages;

use App\Filament\Resources\Sales\CustomerPaymentResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditCustomerPayment extends EditRecord
{
    protected static string $resource = CustomerPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
