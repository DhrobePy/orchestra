<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoodsReceivedNote extends Model
{
    use HasFactory;

    protected $table = 'goods_received_notes';

    // No softDeletes — GRNs are immutable financial records

    protected $fillable = [
        'uuid',
        'grn_number',
        'purchase_order_id',
        'po_number',
        'grn_date',
        'supplier_id',
        'supplier_name',
        'branch_id',
        'receiving_branch',
        'truck_number',
        'vehicle_number',
        'driver_name',
        'delivery_note_number',
        'expected_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'unit_of_measure',
        'unit_price',
        'total_value',
        'weight_variance',
        'variance_percentage',
        'variance_type',
        'variance_remarks',
        'payment_basis_override',
        'grn_status',
        'verified_by',
        'verified_at',
        'posted_by',
        'posted_at',
        'journal_entry_id',
        'condition_notes',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'grn_date'           => 'date',
        'verified_at'        => 'datetime',
        'posted_at'          => 'datetime',
        'expected_quantity'  => 'decimal:2',
        'received_quantity'  => 'decimal:2',
        'accepted_quantity'  => 'decimal:2',
        'rejected_quantity'  => 'decimal:2',
        'unit_price'         => 'decimal:4',
        'total_value'        => 'decimal:2',
        'weight_variance'    => 'decimal:2',
        'variance_percentage' => 'decimal:4',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $grn) {
            if (empty($grn->uuid)) {
                $grn->uuid = (string) Str::uuid();
            }

            if (empty($grn->created_by) && Auth::check()) {
                $grn->created_by = Auth::id();
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class, 'grn_id');
    }

    public function weightVariances(): HasMany
    {
        return $this->hasMany(GrnWeightVariance::class, 'grn_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getEffectivePaymentBasis(): string
    {
        return $this->payment_basis_override
            ?? ($this->purchaseOrder?->payment_basis ?? config('procurement.defaults.payment_basis', 'received_qty'));
    }

    public function getPayableValue(): float
    {
        $basis = $this->getEffectivePaymentBasis();

        if ($basis === 'expected_qty') {
            $qty = $this->expected_quantity ?? $this->received_quantity;
        } else {
            $qty = $this->received_quantity;
        }

        return round((float) $qty * (float) $this->unit_price, 2);
    }

    public function isVerified(): bool
    {
        return in_array($this->grn_status, ['verified', 'posted']);
    }

    public function isPosted(): bool
    {
        return $this->grn_status === 'posted';
    }
}
