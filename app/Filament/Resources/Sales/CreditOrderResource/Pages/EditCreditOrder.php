<?php

namespace App\Filament\Resources\Sales\CreditOrderResource\Pages;

use App\Filament\Resources\Sales\CreditOrderResource;
use App\Models\CreditOrder;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCreditOrder extends EditRecord
{
    protected static string $resource = CreditOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => CreditOrderResource::canDelete($this->getRecord())),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->getRecord()->status !== CreditOrder::STATUS_DRAFT
            && ! Auth::user()->hasAnyRole(['super_admin', 'filament_admin'])) {
            Notification::make()
                ->title('This order cannot be edited in its current status.')
                ->danger()
                ->send();
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // Repeater items are saved via relationship — recalculate totals from real data.
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
