<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'name', 'company_name', 'contact_person', 'phone', 'email',
        'address', 'photo', 'credit_limit', 'credit_balance', 'payment_terms',
        'branch_id', 'is_active',
    ];

    protected $casts = [
        'credit_limit'   => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function creditOrders(): HasMany
    {
        return $this->hasMany(CreditOrder::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedger::class)->orderBy('date')->orderBy('id');
    }
}
