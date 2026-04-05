<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'supplier_ledger_entries';

    // Immutable — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'transaction_date',
        'transaction_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'debit_amount',
        'credit_amount',
        'running_balance',
        'description',
        'branch_id',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit_amount'     => 'decimal:2',
        'credit_amount'    => 'decimal:2',
        'running_balance'  => 'decimal:2',
        'created_at'       => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
