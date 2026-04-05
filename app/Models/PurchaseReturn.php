<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'uuid',
        'return_number',
        'purchase_order_id',
        'grn_id',
        'supplier_id',
        'return_date',
        'return_reason',
        'description',
        'subtotal',
        'tax_amount',
        'total_amount',
        'refund_method',
        'refund_status',
        'status',
        'journal_entry_id',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'approved_at'  => 'datetime',
        'subtotal'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $return) {
            if (empty($return->uuid)) {
                $return->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
