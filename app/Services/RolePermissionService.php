<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Entity;
use App\Models\Module;
use App\Models\RoleConfiguration;
use App\Models\RoleEntityAccess;
use App\Models\RoleModuleAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Central RBAC service — resolves the three permission dimensions:
 *   1. Boolean action toggles  (can_view, can_create, etc.)
 *   2. Numeric limits           (approval_limit, discount_limit_pct, etc.)
 *   3. Scope restrictions       (own_records_only, hidden_fields, etc.)
 *
 * Resolution order for booleans:
 *   entity-level override (if row exists and column is non-null)
 *     → module-level access row
 *       → deny (false)
 *
 * A role whose RoleConfiguration.bypass_all_restrictions = true
 * (super-admin flag) always returns true for all boolean checks
 * and null for all numeric limits.
 */
class RolePermissionService
{
    private const CACHE_TTL = 300; // 5 minutes per user session

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if the user's primary role can perform $action on a module/entity.
     *
     * @param  \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $action   One of the can_* columns (e.g. 'can_view')
     * @param  int     $moduleId
     * @param  int|null $entityId  When provided, entity-level override is checked first
     */
    public function canDo(mixed $user, string $action, int $moduleId, ?int $entityId = null): bool
    {
        if (!$user) {
            return false;
        }

        // Super-admin bypass
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Entity-level override wins if the column is explicitly set (non-null)
        if ($entityId !== null) {
            $entityAccess = $this->getEntityAccess($user, $entityId);
            if ($entityAccess && $entityAccess->$action !== null) {
                return (bool) $entityAccess->$action;
            }
        }

        // Fall back to module-level
        $moduleAccess = $this->getModuleAccess($user, $moduleId);
        if (!$moduleAccess) {
            return false;
        }

        return (bool) ($moduleAccess->$action ?? false);
    }

    /**
     * Return the numeric limit value for the user's role, or null if no limit.
     * Entity-level overrides are not tracked for limits (module-level only).
     *
     * @param  string  $limitKey  One of the limit columns (e.g. 'approval_limit')
     */
    public function getLimit(mixed $user, string $limitKey, int $moduleId): ?float
    {
        if ($this->isSuperAdmin($user)) {
            return null; // no limit
        }

        $access = $this->getModuleAccess($user, $moduleId);
        if (!$access) {
            return 0.0; // no access row = effectively 0 limit
        }

        return $access->$limitKey; // null = no limit configured
    }

    /**
     * Check whether $value is within the user's numeric limit.
     * Returns true if no limit is set (null) or if value ≤ limit.
     */
    public function isWithinLimit(mixed $user, string $limitKey, float $value, int $moduleId): bool
    {
        $limit = $this->getLimit($user, $limitKey, $moduleId);

        if ($limit === null) {
            return true; // no cap
        }

        return $value <= $limit;
    }

    /**
     * Return the list of field names the user cannot see on a given entity.
     */
    public function getHiddenFields(mixed $user, int $entityId): array
    {
        if ($this->isSuperAdmin($user)) {
            return [];
        }

        $entityAccess = $this->getEntityAccess($user, $entityId);
        return $entityAccess?->hidden_fields ?? [];
    }

    /**
     * Return the list of field names that are read-only for the user.
     */
    public function getReadonlyFields(mixed $user, int $entityId): array
    {
        if ($this->isSuperAdmin($user)) {
            return [];
        }

        $entityAccess = $this->getEntityAccess($user, $entityId);
        return $entityAccess?->readonly_fields ?? [];
    }

    /**
     * Check whether the user is restricted to only their own records.
     * Entity-level 'own_records_only' overrides module-level when non-null.
     */
    public function isOwnRecordsOnly(mixed $user, int $moduleId, ?int $entityId = null): bool
    {
        if ($this->isSuperAdmin($user)) {
            return false;
        }

        if ($entityId !== null) {
            $entityAccess = $this->getEntityAccess($user, $entityId);
            if ($entityAccess && $entityAccess->own_records_only !== null) {
                return (bool) $entityAccess->own_records_only;
            }
        }

        $moduleAccess = $this->getModuleAccess($user, $moduleId);
        return (bool) ($moduleAccess?->own_records_only ?? false);
    }

    /**
     * Check if the user's actions require approval before taking effect.
     */
    public function requiresApproval(mixed $user, int $moduleId, ?int $entityId = null): bool
    {
        if ($this->isSuperAdmin($user)) {
            return false;
        }

        if ($entityId !== null) {
            $entityAccess = $this->getEntityAccess($user, $entityId);
            if ($entityAccess && $entityAccess->requires_approval !== null) {
                return (bool) $entityAccess->requires_approval;
            }
        }

        $moduleAccess = $this->getModuleAccess($user, $moduleId);
        return (bool) ($moduleAccess?->requires_approval ?? false);
    }

    /**
     * Bust the per-user permission cache (call after role change or access edit).
     */
    public function clearCache(mixed $user): void
    {
        if (!$user) {
            return;
        }
        $prefix = "rbac_user_{$user->id}_";
        // Clear module-level cache
        $modules = Module::pluck('id');
        foreach ($modules as $id) {
            Cache::forget("{$prefix}module_{$id}");
        }
        // Clear entity-level cache
        $entities = Entity::pluck('id');
        foreach ($entities as $id) {
            Cache::forget("{$prefix}entity_{$id}");
        }
        Cache::forget("{$prefix}config");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function isSuperAdmin(mixed $user): bool
    {
        $config = $this->getRoleConfig($user);
        if ($config?->bypass_all_restrictions) {
            return true;
        }

        // Also treat Spatie 'super_admin' role name as bypass
        return $user->roles->contains('name', 'super_admin');
    }

    private function getRoleConfig(mixed $user): ?RoleConfiguration
    {
        $roleId = $user->roles->first()?->id;
        if (!$roleId) {
            return null;
        }

        return Cache::remember(
            "rbac_user_{$user->id}_config",
            self::CACHE_TTL,
            fn () => RoleConfiguration::where('role_id', $roleId)->first()
        );
    }

    private function getModuleAccess(mixed $user, int $moduleId): ?RoleModuleAccess
    {
        $roleId = $user->roles->first()?->id;
        if (!$roleId) {
            return null;
        }

        return Cache::remember(
            "rbac_user_{$user->id}_module_{$moduleId}",
            self::CACHE_TTL,
            fn () => RoleModuleAccess::where('role_id', $roleId)
                ->where('module_id', $moduleId)
                ->first()
        );
    }

    private function getEntityAccess(mixed $user, int $entityId): ?RoleEntityAccess
    {
        $roleId = $user->roles->first()?->id;
        if (!$roleId) {
            return null;
        }

        return Cache::remember(
            "rbac_user_{$user->id}_entity_{$entityId}",
            self::CACHE_TTL,
            fn () => RoleEntityAccess::where('role_id', $roleId)
                ->where('entity_id', $entityId)
                ->first()
        );
    }
}
