<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditOrder extends Model
{
    use SoftDeletes;

    // ── Status Constants ──────────────────────────────────────────────────────
    const STATUS_DRAFT                   = 'draft';
    const STATUS_PENDING_APPROVAL        = 'pending_approval';
    const STATUS_ESCALATED               = 'escalated';
    const STATUS_APPROVED                = 'approved';
    const STATUS_IN_PRODUCTION           = 'in_production';
    const STATUS_READY_TO_SHIP           = 'ready_to_ship';
    const STATUS_SHIPPED                 = 'shipped';
    const STATUS_DELIVERED               = 'delivered';
    const STATUS_CANCELLED               = 'cancelled';
    const STATUS_CANCELLATION_REQUESTED  = 'cancellation_requested';

    // ── Payment Status ────────────────────────────────────────────────────────
    const PAYMENT_UNPAID         = 'unpaid';
    const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    const PAYMENT_PAID           = 'paid';

    // ── Priority ──────────────────────────────────────────────────────────────
    const PRIORITY_URGENT = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_LOW    = 3;

    protected $table = 'credit_orders';

    protected $fillable = [
        'customer_id', 'branch_id', 'assigned_branch_id',
        'order_number', 'order_date', 'delivery_date', 'delivery_address',
        'status', 'priority', 'payment_status',
        'subtotal', 'discount', 'tax', 'total', 'paid_amount', 'balance',
        'notes',
        'approved_by', 'approved_at',
        'escalated_by', 'escalated_at', 'escalation_notes',
        'cancellation_requested_by', 'cancellation_requested_at', 'cancellation_reason',
        'production_started_by', 'production_started_at',
        'qc_notes', 'qc_passed_at',
        'trip_id', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'order_date'                  => 'date',
        'delivery_date'               => 'date',
        'approved_at'                 => 'datetime',
        'escalated_at'                => 'datetime',
        'cancellation_requested_at'   => 'datetime',
        'production_started_at'       => 'datetime',
        'qc_passed_at'                => 'datetime',
        'shipped_at'                  => 'datetime',
        'delivered_at'                => 'datetime',
        'subtotal'                    => 'decimal:2',
        'discount'                    => 'decimal:2',
        'tax'                         => 'decimal:2',
        'total'                       => 'decimal:2',
        'paid_amount'                 => 'decimal:2',
        'balance'                     => 'decimal:2',
        'priority'                    => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
            if (empty($order->status)) {
                $order->status = self::STATUS_DRAFT;
            }
            if (empty($order->priority)) {
                $order->priority = self::PRIORITY_NORMAL;
            }
            if (empty($order->payment_status)) {
                $order->payment_status = self::PAYMENT_UNPAID;
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'CR-' . now()->format('Ymd');
        $last   = static::where('order_number', 'like', $prefix . '-%')
            ->orderByDesc('order_number')
            ->value('order_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT                  => 'Draft',
            self::STATUS_PENDING_APPROVAL       => 'Pending Approval',
            self::STATUS_ESCALATED              => 'Escalated',
            self::STATUS_APPROVED               => 'Approved',
            self::STATUS_IN_PRODUCTION          => 'In Production',
            self::STATUS_READY_TO_SHIP          => 'Ready to Ship',
            self::STATUS_SHIPPED                => 'Shipped',
            self::STATUS_DELIVERED              => 'Delivered',
            self::STATUS_CANCELLED              => 'Cancelled',
            self::STATUS_CANCELLATION_REQUESTED => 'Cancellation Requested',
            default                             => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT                  => 'gray',
            self::STATUS_PENDING_APPROVAL       => 'warning',
            self::STATUS_ESCALATED              => 'danger',
            self::STATUS_APPROVED               => 'info',
            self::STATUS_IN_PRODUCTION          => 'primary',
            self::STATUS_READY_TO_SHIP          => 'success',
            self::STATUS_SHIPPED                => 'success',
            self::STATUS_DELIVERED              => 'success',
            self::STATUS_CANCELLED              => 'danger',
            self::STATUS_CANCELLATION_REQUESTED => 'warning',
            default                             => 'gray',
        };
    }

    public static function priorityLabel(int $priority): string
    {
        return match ($priority) {
            self::PRIORITY_URGENT => '🔴 Urgent',
            self::PRIORITY_NORMAL => '🟡 Normal',
            self::PRIORITY_LOW    => '🟢 Low',
            default               => 'Normal',
        };
    }

    public static function allStatuses(): array
    {
        return [
            self::STATUS_DRAFT                  => 'Draft',
            self::STATUS_PENDING_APPROVAL       => 'Pending Approval',
            self::STATUS_ESCALATED              => 'Escalated',
            self::STATUS_APPROVED               => 'Approved',
            self::STATUS_IN_PRODUCTION          => 'In Production',
            self::STATUS_READY_TO_SHIP          => 'Ready to Ship',
            self::STATUS_SHIPPED                => 'Shipped',
            self::STATUS_DELIVERED              => 'Delivered',
            self::STATUS_CANCELLED              => 'Cancelled',
            self::STATUS_CANCELLATION_REQUESTED => 'Cancellation Requested',
        ];
    }

    // ── Instance helpers ──────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'escalated_by');
    }

    public function productionStartedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'production_started_by');
    }

    public function cancellationRequestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancellation_requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditOrderItem::class, 'order_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CreditOrderStatusHistory::class);
    }
}
