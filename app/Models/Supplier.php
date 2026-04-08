<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'uuid',
        'supplier_code',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'district',
        'country',
        'tax_id',
        'payment_terms',
        'credit_limit',
        'opening_balance',
        'current_balance',
        'supplier_type',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'currency',
        'notes',
        'photo',
        'created_by',
    ];

    protected $casts = [
        'credit_limit'    => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'deleted_at'      => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $supplier) {
            if (empty($supplier->uuid)) {
                $supplier->uuid = (string) Str::uuid();
            }

            if (empty($supplier->supplier_code)) {
                $supplier->supplier_code = static::generateSupplierCode();
            }

            if (empty($supplier->created_by) && Auth::check()) {
                $supplier->created_by = Auth::id();
            }
        });
    }

    protected static function generateSupplierCode(): string
    {
        $prefix = 'SUP';
        $last = static::withTrashed()
            ->where('supplier_code', 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->value('supplier_code');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return $prefix . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getFormattedBalance(): string
    {
        return number_format((float) $this->current_balance, 2);
    }
}
