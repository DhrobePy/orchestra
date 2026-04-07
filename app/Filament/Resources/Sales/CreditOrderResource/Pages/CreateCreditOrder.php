<?php

namespace App\Filament\Resources\Sales\CreditOrderResource\Pages;

use App\Filament\Resources\Sales\CreditOrderResource;
use App\Models\CreditOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditOrder extends CreateRecord
{
    protected static string $resource = CreditOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty(trim($data['order_number'] ?? ''))) {
            $data['order_number'] = CreditOrder::generateOrderNumber();
        }

        $data['status']         = CreditOrder::STATUS_DRAFT;
        $data['payment_status'] = CreditOrder::PAYMENT_UNPAID;
        $data['priority']       = $data['priority'] ?? CreditOrder::PRIORITY_NORMAL;

        // Repeater items are saved via relationship AFTER create.
        // Actual totals are recalculated in afterCreate().
        $data['subtotal'] = 0;
        $data['total']    = 0;
        $data['balance']  = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Items are now persisted — recalculate totals from real saved data.
        $this->recalculateTotals();
    }

    private function recalculateTotals(): void
    {
        $record   = $this->getRecord()->fresh(['items']);
        $subtotal = $record->items->sum('subtotal');
        $discount = (float) $record->discount;
        $tax      = (float) $record->tax;
        $paid     = (float) $record->paid_amount;
        $total    = max(0, $subtotal - $discount + $tax);
        $balance  = max(0, $total - $paid);

        $paymentStatus = CreditOrder::PAYMENT_UNPAID;
        if ($total > 0) {
            $paymentStatus = $paid >= $total
                ? CreditOrder::PAYMENT_PAID
                : ($paid > 0 ? CreditOrder::PAYMENT_PARTIALLY_PAID : CreditOrder::PAYMENT_UNPAID);
        }

        $record->update([
            'subtotal'       => round($subtotal, 2),
            'total'          => round($total, 2),
            'balance'        => round($balance, 2),
            'payment_status' => $paymentStatus,
        ]);
    }
}
