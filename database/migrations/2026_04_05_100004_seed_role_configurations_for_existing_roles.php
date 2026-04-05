<?php

use App\Models\RoleConfiguration;
use App\Models\RoleModuleAccess;
use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Ensure every existing Spatie role has a role_configurations row,
 * and every (role × module) pair has a role_module_access row.
 *
 * This is a data migration — it is safe to run multiple times
 * (uses updateOrCreate / insertOrIgnore).
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles   = Role::all();
        $modules = Module::all();
        $now     = now();

        foreach ($roles as $role) {
            // Ensure role_configurations row exists
            RoleConfiguration::firstOrCreate(
                ['role_id' => $role->id],
                [
                    'description'             => '',
                    'color'                   => '#6b7280',
                    'is_active'               => true,
                    'bypass_all_restrictions' => $role->name === 'super_admin',
                ]
            );

            // Ensure role_module_access rows exist (one per module)
            $existingModuleIds = RoleModuleAccess::where('role_id', $role->id)
                ->pluck('module_id')
                ->flip();

            $rows = $modules
                ->reject(fn ($m) => $existingModuleIds->has($m->id))
                ->map(fn ($module) => [
                    'role_id'             => $role->id,
                    'module_id'           => $module->id,
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
                    'own_records_only'    => false,
                    'branch_records_only' => false,
                    'requires_approval'   => false,
                    'can_override_price'  => false,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ])
                ->toArray();

            if (!empty($rows)) {
                RoleModuleAccess::insertOrIgnore($rows);
            }
        }
    }

    public function down(): void
    {
        // Intentionally not dropping seed data — run fresh migration to reset
    }
};
