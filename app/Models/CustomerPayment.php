<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'branch_id', 'bank_account_id', 'payment_date', 'amount',
        'payment_method', 'reference', 'notes', 'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REVERSED  = 'reversed';

    public static function methodLabel(string $method): string
    {
        return match($method) {
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'mobile_banking'=> 'Mobile Banking',
            default         => ucfirst($method),
        };
    }

    public function customer(): BelongsTo    { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo      { return $this->belongsTo(Branch::class); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function allocations(): HasMany   { return $this->hasMany(PaymentAllocation::class, 'payment_id'); }
}
