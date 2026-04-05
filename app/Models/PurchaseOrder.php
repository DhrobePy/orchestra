<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'uuid',
        'po_number',
        'po_date',
        'supplier_id',
        'supplier_name',
        'branch_id',
        'branch_name',
        'commodity_description',
        'origin',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'total_order_value',
        'expected_delivery_date',
        'payment_basis',
        'payment_terms',
        'credit_days',
        'advance_percentage',
        'total_received_qty',
        'total_received_value',
        'total_paid',
        'advance_paid',
        'balance_payable',
        'qty_yet_to_receive',
        'po_status',
        'delivery_status',
        'payment_status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'terms_conditions',
        'internal_notes',
        'currency',
        'exchange_rate',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'po_date'                => 'date',
        'expected_delivery_date' => 'date',
        'submitted_at'           => 'datetime',
        'approved_at'            => 'datetime',
        'deleted_at'             => 'datetime',
        'quantity'               => 'decimal:2',
        'unit_price'             => 'decimal:4',
        'total_order_value'      => 'decimal:2',
        'total_received_qty'     => 'decimal:2',
        'total_received_value'   => 'decimal:2',
        'total_paid'             => 'decimal:2',
        'advance_paid'           => 'decimal:2',
        'balance_payable'        => 'decimal:2',
        'qty_yet_to_receive'     => 'decimal:2',
        'advance_percentage'     => 'decimal:2',
        'exchange_rate'          => 'decimal:4',
        'po_status'              => 'string',
        'delivery_status'        => 'string',
        'payment_status'         => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $po) {
            if (empty($po->uuid)) {
                $po->uuid = (string) Str::uuid();
            }

            if (empty($po->created_by) && Auth::check()) {
                $po->created_by = Auth::id();
            }
        });

        static::updating(function (self $po) {
            if (Auth::check()) {
                $po->updated_by = Auth::id();
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class)->where('is_posted', true);
    }

    public function allPayments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class, 'supplier_id', 'supplier_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helper Methods ─────────────────────────────────────────────────────────

    /**
     * Returns total payable based on payment_basis config.
     * For a quick estimate before any GRNs — use ProcurementService for accuracy.
     */
    public function getPayableBasisAmount(): float
    {
        $basis = $this->payment_basis ?? config('procurement.defaults.payment_basis', 'received_qty');

        if ($basis === 'expected_qty') {
            return round((float) $this->quantity * (float) $this->unit_price, 2);
        }

        return round((float) $this->total_received_qty * (float) $this->unit_price, 2);
    }

    public function isEditable(): bool
    {
        return in_array($this->po_status, ['draft', 'submitted']);
    }

    public function canBeApproved(): bool
    {
        return $this->po_status === 'submitted';
    }

    public function canBeReceived(): bool
    {
        return in_array($this->po_status, ['approved', 'partial']);
    }

    public function isFullyReceived(): bool
    {
        return in_array($this->delivery_status, ['completed', 'over_received']);
    }

    public function isFullyPaid(): bool
    {
        return in_array($this->payment_status, ['paid', 'overpaid']);
    }
}
