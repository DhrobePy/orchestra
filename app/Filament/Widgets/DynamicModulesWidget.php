<?php

namespace App\Filament\Widgets;

use App\Models\Module;
use App\Services\DashboardWidgetService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicModulesWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;
    protected ?string $heading = 'Module Summaries';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $roleName  = auth()->user()?->getRoleNames()->first() ?? 'super_admin';
        $moduleIds = DashboardWidgetService::getEnabledModuleIds($roleName);

        if (empty($moduleIds)) {
            return [];
        }

        $modules = Module::with('entities')
            ->whereIn('id', $moduleIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stats = [];

        foreach ($modules as $module) {
            $entityStats   = $this->buildModuleStats($module);
            $entityCount   = $module->entities->count();

            // Filter out null counts (tables not yet migrated)
            $validStats    = array_filter($entityStats, fn ($e) => $e['count'] !== null);
            $totalRecords  = (int) array_sum(array_column($validStats, 'count'));

            // Module header — value must be int/float, not a string
            $stats[] = Stat::make($module->name, $totalRecords)
                ->description(
                    $entityCount . ' ' . ($entityCount === 1 ? 'entity' : 'entities') . ' · ' .
                    count($validStats) . ' table' . (count($validStats) === 1 ? '' : 's') . ' active'
                )
                ->color('primary')
                ->icon($module->icon ?? 'heroicon-o-cube');

            // One stat per entity (value = raw int)
            foreach ($validStats as $entity) {
                $stats[] = Stat::make($entity['name'], (int) $entity['count'])
                    ->description($entity['table'])
                    ->color($this->colorForCount((int) $entity['count']))
                    ->icon('heroicon-o-table-cells');
            }
        }

        return $stats;
    }

    // ── Build per-entity stats for a module ────────────────────────────────────

    protected function buildModuleStats(Module $module): array
    {
        $out = [];

        foreach ($module->entities as $entity) {
            $table = $entity->table_name ?? null;
            $count = null;

            if ($table && Schema::hasTable($table)) {
                try {
                    // Respect soft deletes if the column exists
                    $q = DB::table($table);
                    if (Schema::hasColumn($table, 'deleted_at')) {
                        $q->whereNull('deleted_at');
                    }
                    $count = $q->count();
                } catch (\Throwable) {
                    $count = null;
                }
            }

            $out[] = [
                'name'  => $entity->name,
                'table' => $table ?? '—',
                'count' => $count,
            ];
        }

        return $out;
    }

    // ── Color scale based on record count ─────────────────────────────────────

    protected function colorForCount(int $count): string
    {
        if ($count === 0) {
            return 'gray';
        }
        if ($count < 10) {
            return 'warning';
        }
        return 'success';
    }
}
