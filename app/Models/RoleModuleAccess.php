<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class RoleModuleAccess extends Model
{
    protected $table = 'role_module_access';

    protected $fillable = [
        'role_id',
        'module_id',
        // Boolean toggles
        'can_view', 'can_create', 'can_edit', 'can_delete',
        'can_export', 'can_import', 'can_print',
        'can_approve', 'can_reject', 'can_bulk_action',
        // Numeric limits
        'approval_limit', 'discount_limit_pct', 'max_order_value',
        'daily_create_limit', 'max_edit_age_days', 'max_void_age_days',
        'max_refund_pct', 'credit_limit_override', 'max_items_per_order',
        // Scope flags
        'own_records_only', 'branch_records_only', 'requires_approval', 'can_override_price',
    ];

    protected $casts = [
        'can_view'            => 'boolean',
        'can_create'          => 'boolean',
        'can_edit'            => 'boolean',
        'can_delete'          => 'boolean',
        'can_export'          => 'boolean',
        'can_import'          => 'boolean',
        'can_print'           => 'boolean',
        'can_approve'         => 'boolean',
        'can_reject'          => 'boolean',
        'can_bulk_action'     => 'boolean',
        'own_records_only'    => 'boolean',
        'branch_records_only' => 'boolean',
        'requires_approval'   => 'boolean',
        'can_override_price'  => 'boolean',
    ];

    /** All 10 boolean action toggles — used by ManageRoles UI loop */
    public static array $actions = [
        'can_view', 'can_create', 'can_edit', 'can_delete',
        'can_export', 'can_import', 'can_print',
        'can_approve', 'can_reject', 'can_bulk_action',
    ];

    /** Numeric limit columns and their human labels */
    public static array $limits = [
        'approval_limit'       => 'Approval Limit (BDT)',
        'discount_limit_pct'   => 'Max Discount %',
        'max_order_value'      => 'Max Order Value (BDT)',
        'daily_create_limit'   => 'Daily Create Limit',
        'max_edit_age_days'    => 'Max Edit Age (days)',
        'max_void_age_days'    => 'Max Void Age (days)',
        'max_refund_pct'       => 'Max Refund %',
        'credit_limit_override' => 'Credit Limit Override (BDT)',
        'max_items_per_order'  => 'Max Items / Order',
    ];

    /** Scope flag columns and their human labels */
    public static array $scopeFlags = [
        'own_records_only'    => 'Own Records Only',
        'branch_records_only' => 'Branch Records Only',
        'requires_approval'   => 'Requires Approval',
        'can_override_price'  => 'Can Override Price',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
