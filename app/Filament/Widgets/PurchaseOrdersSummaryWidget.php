<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseOrdersSummaryWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $total    = PurchaseOrder::count();
        $draft    = PurchaseOrder::where('po_status', 'draft')->count();
        $pending  = PurchaseOrder::where('po_status', 'submitted')->count();
        $approved = PurchaseOrder::where('po_status', 'approved')->count();
        $received = PurchaseOrder::whereIn('delivery_status', ['completed', 'over_received'])->count();
        $unpaid   = PurchaseOrder::where('payment_status', 'unpaid')->count();

        return [
            Stat::make('Total POs', $total)
                ->description('All purchase orders')
                ->color('primary'),

            Stat::make('Awaiting Approval', $pending)
                ->description('Submitted, not yet approved')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Fully Received', $received)
                ->description('GRN completed')
                ->color('success'),

            Stat::make('Unpaid', $unpaid)
                ->description('Outstanding payments')
                ->color($unpaid > 0 ? 'danger' : 'success'),
        ];
    }
}
