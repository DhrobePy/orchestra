<?php

namespace App\Services;

use App\Models\DashboardWidgetSetting;
use App\Models\Module;
use Illuminate\Support\Facades\Cache;

class DashboardWidgetService
{
    // ── Static operational widgets (hardcoded business logic) ──────────────────

    const STATIC_WIDGETS = [
        'orders_summary' => [
            'label'           => 'Sales Orders Summary',
            'class'           => \App\Filament\Widgets\OrdersSummaryWidget::class,
            'default_enabled' => true,
            'description'     => 'Order counts by status (draft, approved, delivered…)',
            'group'           => 'Operations',
        ],
        'purchase_orders_summary' => [
            'label'           => 'Purchase Orders Summary',
            'class'           => \App\Filament\Widgets\PurchaseOrdersSummaryWidget::class,
            'default_enabled' => true,
            'description'     => 'PO counts by status and payment state',
            'group'           => 'Operations',
        ],
        'recent_orders' => [
            'label'           => 'Recent Orders Table',
            'class'           => \App\Filament\Widgets\RecentOrdersWidget::class,
            'default_enabled' => true,
            'description'     => 'Latest orders — staff sees only their own',
            'group'           => 'Operations',
        ],
        'payments_summary' => [
            'label'           => 'Customer Payments Summary',
            'class'           => \App\Filament\Widgets\PaymentsSummaryWidget::class,
            'default_enabled' => false,
            'description'     => 'Today\'s collections and monthly totals',
            'group'           => 'Operations',
        ],
    ];

    // ── Full widget registry (static + per-module from DB) ─────────────────────

    public static function allWidgets(): array
    {
        $widgets = self::STATIC_WIDGETS;

        // Append one entry per active module — all share DynamicModulesWidget class
        // but are keyed separately so admin can toggle each module independently.
        try {
            Module::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'icon'])
                ->each(function (Module $module) use (&$widgets) {
                    $widgets["module_{$module->id}"] = [
                        'label'           => $module->name . ' Summary',
                        'class'           => \App\Filament\Widgets\DynamicModulesWidget::class,
                        'default_enabled' => true,
                        'description'     => "Entity record counts for the {$module->name} module",
                        'group'           => 'Modules',
                        'module_id'       => $module->id,
                        'module_name'     => $module->name,
                        'module_icon'     => $module->icon,
                    ];
                });
        } catch (\Throwable) {
            // DB may not be ready during migrations — skip silently
        }

        return $widgets;
    }

    // ── Get enabled widget classes for a role ──────────────────────────────────

    public static function getEnabledWidgets(string $roleName): array
    {
        $allWidgets = self::allWidgets();

        $saved = Cache::remember("dash_widgets_{$roleName}", 300, function () use ($roleName) {
            return DashboardWidgetSetting::where('role_name', $roleName)
                ->orderBy('sort_order')
                ->pluck('is_enabled', 'widget_key')
                ->toArray();
        });

        $classes     = [];
        $addedStatic = [];       // deduplicate static widget classes
        $moduleIds   = [];       // collect enabled module IDs for DynamicModulesWidget

        foreach ($allWidgets as $key => $meta) {
            $enabled = $saved[$key] ?? $meta['default_enabled'];

            if (!$enabled) {
                continue;
            }

            if (str_starts_with($key, 'module_')) {
                // Collect module IDs for the single DynamicModulesWidget instance
                $moduleIds[] = $meta['module_id'];
            } else {
                $class = $meta['class'];
                if (!in_array($class, $addedStatic, true)) {
                    $addedStatic[] = $class;
                    $classes[]     = $class;
                }
            }
        }

        // Add DynamicModulesWidget ONCE if any modules are enabled
        if (!empty($moduleIds)) {
            // Store the enabled module IDs so the widget can read them
            // We use a request-scoped singleton to pass data to the widget
            app()->instance('dashboard.enabled_module_ids.' . $roleName, $moduleIds);
            $classes[] = \App\Filament\Widgets\DynamicModulesWidget::class;
        }

        return $classes;
    }

    // ── Get settings array for a role (for admin UI) ──────────────────────────

    public static function getSettingsForRole(string $roleName): array
    {
        $saved = DashboardWidgetSetting::where('role_name', $roleName)
            ->pluck('is_enabled', 'widget_key')
            ->toArray();

        $out = [];
        foreach (self::allWidgets() as $key => $meta) {
            $out[$key] = (bool) ($saved[$key] ?? $meta['default_enabled']);
        }
        return $out;
    }

    // ── Save settings for a role ───────────────────────────────────────────────

    public static function saveSettingsForRole(string $roleName, array $settings): void
    {
        $sort = 0;
        foreach ($settings as $key => $enabled) {
            // Only save keys that are in our current widget registry
            if (!array_key_exists($key, self::allWidgets())) {
                continue;
            }
            DashboardWidgetSetting::updateOrCreate(
                ['role_name' => $roleName, 'widget_key' => $key],
                ['is_enabled' => (bool) $enabled, 'sort_order' => $sort++]
            );
        }
        Cache::forget("dash_widgets_{$roleName}");
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function bustCache(string $roleName): void
    {
        Cache::forget("dash_widgets_{$roleName}");
    }

    public static function getEnabledModuleIds(string $roleName): array
    {
        $all   = self::allWidgets();
        $saved = DashboardWidgetSetting::where('role_name', $roleName)
            ->pluck('is_enabled', 'widget_key')
            ->toArray();

        $ids = [];
        foreach ($all as $key => $meta) {
            if (!str_starts_with($key, 'module_')) {
                continue;
            }
            $enabled = $saved[$key] ?? $meta['default_enabled'];
            if ($enabled) {
                $ids[] = $meta['module_id'];
            }
        }
        return $ids;
    }
}
