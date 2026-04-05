<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Module;
use App\Models\RoleModuleAccess;
use Spatie\Permission\Models\Role;

/**
 * When a new Module is created, automatically insert a role_module_access row
 * for every existing role — with all toggles set to FALSE (secure-by-default).
 *
 * This ensures no role silently gains access to a new module until an admin
 * explicitly opens the permissions in ManageRoles.
 */
class RoleModuleAccessObserver
{
    public function created(Module $module): void
    {
        $roleIds = Role::pluck('id');

        $now  = now();
        $rows = $roleIds->map(fn (int|string $roleId) => [
            'role_id'   => $roleId,
            'module_id' => $module->id,
            // All toggles default to false
            'can_view'            => false,
            'can_create'          => false,
            'can_edit'            => false,
            'can_delete'          => false,
            'can_export'          => false,
            'can_import'          => false,
            'can_print'           => false,
            'can_approve'         => false,
            'can_reject'          => false,
            'can_bulk_action'     => false,
            // Scope flags
            'own_records_only'    => false,
            'branch_records_only' => false,
            'requires_approval'   => false,
            'can_override_price'  => false,
            // Limits are null (no limit set)
            'approval_limit'        => null,
            'discount_limit_pct'    => null,
            'max_order_value'       => null,
            'daily_create_limit'    => null,
            'max_edit_age_days'     => null,
            'max_void_age_days'     => null,
            'max_refund_pct'        => null,
            'credit_limit_override' => null,
            'max_items_per_order'   => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // insertOrIgnore so re-runs don't cause duplicate-key errors
        RoleModuleAccess::insertOrIgnore($rows);
    }
}
