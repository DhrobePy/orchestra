<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CreditOrder $order,
        public readonly float $amount,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'    => "Payment Recorded: {$this->order->order_number}",
            'body'     => "৳" . number_format($this->amount, 2) . " received from {$this->order->customer?->name}. Remaining balance: ৳" . number_format((float) $this->order->balance, 2),
            'icon'     => 'heroicon-o-banknotes',
            'color'    => 'success',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
