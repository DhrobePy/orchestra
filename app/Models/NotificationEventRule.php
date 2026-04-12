<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationEventRule extends Model
{
    protected $fillable = [
        'event_key', 'channel_id',
        'recipient_mode', 'recipient_identifier',
        'message_template', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    // ── Event catalogue ────────────────────────────────────────────────────────

    public static function eventOptions(): array
    {
        return [
            'order.created'          => '🛒 New Sales Order created',
            'order.status_changed'   => '🔄 Sales Order status changed',
            'order.approved'         => '✅ Sales Order approved',
            'order.delivered'        => '🚚 Sales Order delivered',
            'order.cancelled'        => '❌ Sales Order cancelled',
            'po.created'             => '📦 New Purchase Order created',
            'po.approved'            => '✅ Purchase Order approved',
            'grn.received'           => '📥 Goods Received (GRN)',
            'payment.received'       => '💰 Customer Payment received',
            'po.payment.made'        => '💸 Purchase Payment made',
        ];
    }

    public static function recipientModeOptions(): array
    {
        return [
            'channel_default' => 'Channel default (group/chat)',
            'role'            => 'All users in role',
            'user'            => 'Specific user',
        ];
    }

    // ── Default message templates ──────────────────────────────────────────────

    public static function defaultTemplate(string $eventKey): string
    {
        return match ($eventKey) {
            'order.created'        => "📋 *New Order* #{order_number}\nCustomer: {customer}\nTotal: {total}\nBy: {created_by}",
            'order.status_changed' => "🔄 Order #{order_number} → *{status}*\nCustomer: {customer}",
            'order.approved'       => "✅ Order #{order_number} *APPROVED*\nCustomer: {customer}\nTotal: {total}",
            'order.delivered'      => "🚚 Order #{order_number} *DELIVERED*\nCustomer: {customer}",
            'order.cancelled'      => "❌ Order #{order_number} *CANCELLED*\nReason: {reason}",
            'po.created'           => "📦 *New PO* #{po_number}\nSupplier: {supplier}\nTotal: {total}",
            'po.approved'          => "✅ PO #{po_number} *APPROVED*\nSupplier: {supplier}",
            'grn.received'         => "📥 *GRN Received* for PO #{po_number}\nSupplier: {supplier}\nQty: {qty}",
            'payment.received'     => "💰 *Payment Received* from {customer}\nAmount: {amount}",
            'po.payment.made'      => "💸 *Purchase Payment* to {supplier}\nAmount: {amount}",
            default                => "Event: {event_key}",
        };
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id');
    }
}
