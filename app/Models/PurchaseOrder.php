<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'total'         => 'decimal:2',
    ];


    protected static function booted(): void
    {
        static::creating(function ($po) {
            $po->po_number = 'PO-' . str_pad(
                (PurchaseOrder::withTrashed()->count() + 1),
                5, '0', STR_PAD_LEFT
            );
            $po->created_by = auth()->id();
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_cost');
        $this->total    = $this->subtotal + $this->tax;
        $this->save();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }
}
