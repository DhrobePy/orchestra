<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_name', 'account_name', 'account_number',
        'branch_code', 'account_type', 'opening_balance',
        'current_balance', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->bank_name . ' — ' . $this->account_name . ' (' . $this->account_number . ')';
    }

    public static function activeOptions(): array
    {
        return static::where('is_active', true)
            ->get()
            ->mapWithKeys(fn ($b) => [$b->id => $b->bank_name . ' — ' . $b->account_name . ' (' . $b->account_number . ')'])
            ->toArray();
    }
}
