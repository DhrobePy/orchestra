<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CreditOrder $order,
        public readonly string $reason = '',
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'    => "Order Cancelled: {$this->order->order_number}",
            'body'     => "Order for {$this->order->customer?->name} has been cancelled." . ($this->reason ? " Reason: {$this->reason}" : ''),
            'icon'     => 'heroicon-o-x-circle',
            'color'    => 'danger',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
