<?php

namespace App\Filament\Widgets;

use App\Models\CreditOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersSummaryWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $q = CreditOrder::query();

        // Staff sees only their own orders
        if (filament()->getCurrentPanel()?->getId() === 'app') {
            $q->where('created_by', auth()->id());
        }

        $base = clone $q;

        $total     = (clone $base)->count();
        $draft     = (clone $base)->where('status', CreditOrder::STATUS_DRAFT)->count();
        $pending   = (clone $base)->where('status', CreditOrder::STATUS_PENDING_APPROVAL)->count();
        $approved  = (clone $base)->whereIn('status', [
            CreditOrder::STATUS_APPROVED,
            CreditOrder::STATUS_IN_PRODUCTION,
            CreditOrder::STATUS_READY_TO_SHIP,
        ])->count();
        $delivered = (clone $base)->where('status', CreditOrder::STATUS_DELIVERED)->count();
        $cancelled = (clone $base)->where('status', CreditOrder::STATUS_CANCELLED)->count();

        return [
            Stat::make('Total Orders', $total)
                ->description('All time')
                ->color('primary'),

            Stat::make('Pending Approval', $pending)
                ->description('Awaiting review')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Active / In Progress', $approved)
                ->description('Approved → In production → Ready to ship')
                ->color('info'),

            Stat::make('Delivered', $delivered)
                ->description('Completed orders')
                ->color('success'),
        ];
    }
}
