<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'unit_cost'         => 'decimal:2',
        'total_cost'        => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $item->total_cost = $item->quantity * $item->unit_cost;
        });

        static::saved(function ($item) {
            $item->purchaseOrder->recalculateTotals();
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function remainingQuantity(): float
    {
        return $this->quantity - $this->received_quantity;
    }
}
