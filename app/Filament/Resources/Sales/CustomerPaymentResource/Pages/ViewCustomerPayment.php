<?php
namespace App\Filament\Resources\Sales\CustomerPaymentResource\Pages;

use App\Filament\Resources\Sales\CustomerPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerPayment extends ViewRecord
{
    protected static string $resource = CustomerPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
