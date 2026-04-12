<?php

namespace App\Filament\Pages;

use App\Services\DashboardWidgetService;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        $roleName = auth()->user()?->getRoleNames()->first() ?? 'super_admin';
        return DashboardWidgetService::getEnabledWidgets($roleName);
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
