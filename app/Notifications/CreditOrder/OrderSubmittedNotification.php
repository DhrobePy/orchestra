<?php

namespace App\Notifications\CreditOrder;

use App\Models\CreditOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OrderSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CreditOrder $order,
        public readonly string $newStatus,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $label  = $this->newStatus === CreditOrder::STATUS_ESCALATED
            ? '⚠️ Escalated (Insufficient Credit)'
            : '✅ Awaiting Your Approval';

        return [
            'title'    => "New Credit Order: {$this->order->order_number}",
            'body'     => "Customer: {$this->order->customer?->name} | Total: ৳" . number_format((float) $this->order->total, 2) . " | {$label}",
            'icon'     => $this->newStatus === CreditOrder::STATUS_ESCALATED ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-document-text',
            'color'    => $this->newStatus === CreditOrder::STATUS_ESCALATED ? 'warning' : 'info',
            'order_id' => $this->order->id,
            'url'      => "/admin/sales/credit-orders/{$this->order->id}",
        ];
    }
}
