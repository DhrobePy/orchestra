<?php
namespace App\Filament\Resources\Sales\CustomerPaymentResource\Pages;

use App\Filament\Resources\Sales\CustomerPaymentResource;
use App\Models\Customer;
use App\Services\CustomerPaymentService;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPayment extends CreateRecord
{
    protected static string $resource = CustomerPaymentResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $customer = Customer::findOrFail($data['customer_id']);
        $service  = new CustomerPaymentService();

        return $service->collect(
            customer:    $customer,
            amount:      (float) $data['amount'],
            method:      $data['payment_method'] ?? 'cash',
            reference:   $data['reference'] ?? null,
            notes:       $data['notes'] ?? null,
            branchId:    $data['branch_id'] ?? null,
            paymentDate: $data['payment_date'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
