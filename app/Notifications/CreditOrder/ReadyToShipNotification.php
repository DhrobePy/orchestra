<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadyToShipNotification extends Notification
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
            'title'    => "Ready to Ship: {$this->order->order_number}",
            'body'     => "Order for {$this->order->customer?->name} has passed QC and is ready for dispatch.",
            'icon'     => 'heroicon-o-truck',
            'color'    => 'success',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
