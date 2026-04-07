<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderApprovedNotification extends Notification
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
            'title'    => "Order Approved: {$this->order->order_number}",
            'body'     => "Order for {$this->order->customer?->name} (৳" . number_format((float) $this->order->total, 2) . ") has been approved and is ready for production.",
            'icon'     => 'heroicon-o-check-circle',
            'color'    => 'success',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
