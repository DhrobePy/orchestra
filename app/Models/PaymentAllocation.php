<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = ['payment_id', 'order_id', 'amount'];
    protected $casts    = ['amount' => 'decimal:2'];

    public function payment(): BelongsTo { return $this->belongsTo(CustomerPayment::class, 'payment_id'); }
    public function order(): BelongsTo   { return $this->belongsTo(CreditOrder::class, 'order_id'); }
}
