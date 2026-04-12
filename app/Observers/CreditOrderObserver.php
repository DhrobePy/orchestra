<?php

namespace App\Observers;

use App\Models\CreditOrder;
use App\Services\NotificationDispatcher;

class CreditOrderObserver
{
    public function created(CreditOrder $order): void
    {
        NotificationDispatcher::fire('order.created', [
            'order_number' => $order->order_number,
            'customer'     => $order->customer?->name ?? '—',
            'total'        => '৳ ' . number_format((float) $order->total, 2),
            'created_by'   => auth()->user()?->name ?? 'System',
        ]);
    }

    public function updated(CreditOrder $order): void
    {
        if (!$order->wasChanged('status')) {
            return;
        }

        $status = $order->status;

        // Fire specific events for key status transitions
        $specificEvent = match ($status) {
            CreditOrder::STATUS_APPROVED  => 'order.approved',
            CreditOrder::STATUS_DELIVERED => 'order.delivered',
            CreditOrder::STATUS_CANCELLED => 'order.cancelled',
            default                       => null,
        };

        $vars = [
            'order_number' => $order->order_number,
            'customer'     => $order->customer?->name ?? '—',
            'total'        => '৳ ' . number_format((float) $order->total, 2),
            'status'       => CreditOrder::statusLabel($status),
            'reason'       => $order->cancellation_reason ?? '—',
        ];

        // Always fire generic status_changed
        NotificationDispatcher::fire('order.status_changed', $vars);

        // Also fire specific event if applicable
        if ($specificEvent) {
            NotificationDispatcher::fire($specificEvent, $vars);
        }
    }
}
