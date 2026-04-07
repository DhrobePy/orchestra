<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderDeliveredNotification extends Notification
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
            'title'    => "Order Delivered: {$this->order->order_number}",
            'body'     => "Order for {$this->order->customer?->name} has been delivered. Balance due: ৳" . number_format((float) $this->order->balance, 2),
            'icon'     => 'heroicon-o-check-badge',
            'color'    => 'success',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
