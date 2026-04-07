<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly CreditOrder $order) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'    => "Order Shipped: {$this->order->order_number}",
            'body'     => "Order for {$this->order->customer?->name} has been dispatched. Customer ledger updated.",
            'icon'     => 'heroicon-o-truck',
            'color'    => 'info',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
