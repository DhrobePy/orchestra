<?php

namespace App\Providers;

use App\Models\CreditOrder;
use App\Models\Module;
use App\Models\PurchaseOrder;
use App\Models\RoleConfiguration;
use App\Observers\CreditOrderObserver;
use App\Observers\PurchaseOrderObserver;
use App\Observers\RoleModuleAccessObserver;
use App\Services\DynamicMigrationService;
use App\Services\DynamicModelGenerator;
use App\Services\ProcurementService;
use App\Services\RolePermissionService;
use App\Services\SchemaCache;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DynamicModelGenerator::class);
        $this->app->singleton(DynamicMigrationService::class);
        $this->app->singleton(SchemaCache::class);
        $this->app->singleton(RolePermissionService::class);
        $this->app->singleton(ProcurementService::class);
    }

    public function boot(): void
    {
        // Auto-create locked role_module_access rows when a new Module is added
        Module::observe(RoleModuleAccessObserver::class);

        // Dispatch notifications on order / PO lifecycle events
        CreditOrder::observe(CreditOrderObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);

        Filament::serving(function () {
            try {
                $modules = Module::where('is_active', true)
                    ->with('entities')
                    ->get();

                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                $rbac = app(RolePermissionService::class);

                // ── Decide whether to apply RBAC filtering at all ──────────
                // Only filter when:
                //   (a) user is authenticated,
                //   (b) user has at least one role,
                //   (c) that role is NOT a bypass / super_admin role.
                // In every other case we show all nav items (fail-open).
                $applyRbac = false;
                if ($user && $user->roles->isNotEmpty()) {
                    try {
                        // Never filter for Spatie super_admin role
                        if (!$user->roles->contains('name', 'super_admin')) {
                            $roleId = $user->roles->first()->id;
                            $config = RoleConfiguration::where('role_id', $roleId)->first();
                            // Only filter if a config row exists and bypass flag is off
                            $applyRbac = $config !== null && !$config->bypass_all_restrictions;
                        }
                    } catch (\Throwable) {
                        // role_configurations table may not exist yet — show everything
                        $applyRbac = false;
                    }
                }

                $groups = [];
                $items  = [];

                foreach ($modules as $module) {
                    if ($applyRbac) {
                        try {
                            if (!$rbac->canDo($user, 'can_view', $module->id)) {
                                continue;
                            }
                        } catch (\Throwable) {
                            // fail-open on any RBAC error
                        }
                    }

                    $groups[] = NavigationGroup::make($module->name)
                        ->icon($module->icon ?? 'heroicon-o-folder');

                    foreach ($module->entities as $entity) {
                        if ($applyRbac) {
                            try {
                                if (!$rbac->canDo($user, 'can_view', $module->id, $entity->id)) {
                                    continue;
                                }
                            } catch (\Throwable) {
                                // fail-open
                            }
                        }

                        $items[] = NavigationItem::make($entity->name)
                            ->group($module->name)
                            ->url("/admin/dynamic/{$entity->table_name}")
                            ->isActiveWhen(fn () =>
                                request()->segment(3) === $entity->table_name
                            );
                    }
                }

                Filament::registerNavigationGroups($groups);
                Filament::registerNavigationItems($items);

            } catch (\Throwable) {
                // DB not ready — skip silently
            }
        });
    }
}
