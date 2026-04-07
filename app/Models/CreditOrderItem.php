<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditOrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'credit_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'variant_id',
        'quantity', 'unit_price', 'discount', 'discount_type', 'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    // ── Shared calculation helper ─────────────────────────────────────────────
    // per_unit  → discount is ৳ per unit   → discount_amount = qty × discount
    // percent   → discount is %            → discount_amount = (qty × price) × discount/100
    // flat      → discount is ৳ off line   → discount_amount = discount

    public static function calcSubtotal(
        float  $qty,
        float  $price,
        float  $discount,
        string $discountType = 'flat',
    ): float {
        $lineGross      = $qty * $price;
        $discountAmount = match ($discountType) {
            'per_unit' => $qty * $discount,
            'percent'  => $lineGross * ($discount / 100),
            default    => $discount,          // 'flat'
        };
        return max(0, $lineGross - $discountAmount);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CreditOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
