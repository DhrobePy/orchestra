<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'item_type',
        'item_description',
        'item_code',
        'variant_id',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'total_value',
        'received_qty',
        'expected_delivery_date',
        'notes',
    ];

    protected $casts = [
        'quantity'               => 'decimal:2',
        'unit_price'             => 'decimal:4',
        'total_value'            => 'decimal:2',
        'received_qty'           => 'decimal:2',
        'expected_delivery_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item) {
            $item->total_value = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function remainingQty(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_qty);
    }
}
