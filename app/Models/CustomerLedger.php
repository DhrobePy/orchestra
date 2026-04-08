<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedger extends Model
{
    use SoftDeletes;

    protected $table = 'customer_ledger';

    protected $fillable = [
        'customer_id', 'date', 'description',
        'debit', 'credit', 'balance',
        'reference_type', 'reference_id',
    ];

    protected $casts = [
        'date'    => 'date',
        'debit'   => 'decimal:2',
        'credit'  => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditOrder(): BelongsTo
    {
        return $this->belongsTo(CreditOrder::class, 'reference_id');
    }

    /** Is this entry a sale (debit) or a payment (credit)? */
    public function isSale(): bool
    {
        return (float) $this->debit > 0;
    }

    public function isPayment(): bool
    {
        return (float) $this->credit > 0;
    }

    public function typeLabel(): string
    {
        if ($this->isSale())    return 'Sale';
        if ($this->isPayment()) return 'Payment';
        return 'Adjustment';
    }
}
