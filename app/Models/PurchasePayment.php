<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $table = 'purchase_payments';

    protected $fillable = [
        'uuid',
        'voucher_number',
        'payment_date',
        'purchase_order_id',
        'po_number',
        'supplier_id',
        'supplier_name',
        'branch_id',
        'amount_paid',
        'payment_type',
        'payment_method',
        'bank_account_id',
        'bank_name',
        'cash_account_id',
        'handled_by',
        'reference_number',
        'cheque_date',
        'cheque_status',
        'status',
        'is_posted',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'journal_entry_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date'  => 'date',
        'approved_at'  => 'datetime',
        'amount_paid'  => 'decimal:2',
        'is_posted'    => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }

            if (empty($payment->created_by) && Auth::check()) {
                $payment->created_by = Auth::id();
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePosted($query)
    {
        return $query->where('is_posted', true)->where('status', 'posted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'draft');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isPosted(): bool
    {
        return $this->is_posted && $this->status === 'posted';
    }

    public function isPending(): bool
    {
        return $this->status === 'draft';
    }
}
