<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $table = 'purchase_invoices';

    protected $fillable = [
        'uuid',
        'invoice_number',
        'supplier_invoice_number',
        'purchase_order_id',
        'grn_id',
        'supplier_id',
        'branch_id',
        'invoice_date',
        'due_date',
        'invoice_type',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'other_charges',
        'total_amount',
        'paid_amount',
        'balance_due',
        'payment_status',
        'status',
        'journal_entry_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'due_date'        => 'date',
        'subtotal'        => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'other_charges'   => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'balance_due'     => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
