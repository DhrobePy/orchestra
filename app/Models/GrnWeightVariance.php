<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnWeightVariance extends Model
{
    use HasFactory;

    protected $table = 'grn_weight_variances';

    // Immutable log — only created_at
    public $timestamps = false;

    protected $fillable = [
        'grn_id',
        'purchase_order_id',
        'variance_date',
        'expected_quantity',
        'received_quantity',
        'variance_quantity',
        'variance_percentage',
        'variance_type',
        'threshold_percentage',
        'remarks',
        'recorded_by',
        'created_at',
    ];

    protected $casts = [
        'variance_date'        => 'date',
        'expected_quantity'    => 'decimal:2',
        'received_quantity'    => 'decimal:2',
        'variance_quantity'    => 'decimal:2',
        'variance_percentage'  => 'decimal:4',
        'threshold_percentage' => 'decimal:2',
        'created_at'           => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
