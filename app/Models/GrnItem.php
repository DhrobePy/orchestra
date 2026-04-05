<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    use HasFactory;

    protected $table = 'grn_items';

    protected $fillable = [
        'grn_id',
        'purchase_order_item_id',
        'item_type',
        'item_description',
        'item_code',
        'variant_id',
        'ordered_quantity',
        'expected_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'unit_of_measure',
        'unit_price',
        'line_total',
        'weight_variance',
        'variance_percentage',
        'batch_number',
        'expiry_date',
        'storage_location',
        'condition_status',
        'notes',
    ];

    protected $casts = [
        'ordered_quantity'   => 'decimal:2',
        'expected_quantity'  => 'decimal:2',
        'received_quantity'  => 'decimal:2',
        'accepted_quantity'  => 'decimal:2',
        'rejected_quantity'  => 'decimal:2',
        'unit_price'         => 'decimal:4',
        'line_total'         => 'decimal:2',
        'weight_variance'    => 'decimal:2',
        'variance_percentage' => 'decimal:4',
        'expiry_date'        => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }
}
