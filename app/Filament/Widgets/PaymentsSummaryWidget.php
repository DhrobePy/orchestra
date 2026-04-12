<?php

namespace App\Filament\Widgets;

use App\Models\CustomerPayment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PaymentsSummaryWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $today     = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $todayTotal = CustomerPayment::where('status', CustomerPayment::STATUS_CONFIRMED)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $monthTotal = CustomerPayment::where('status', CustomerPayment::STATUS_CONFIRMED)
            ->whereBetween('created_at', [$monthStart, now()])
            ->sum('amount');

        $pendingCount = CustomerPayment::where('status', '!=', CustomerPayment::STATUS_CONFIRMED)
            ->where('status', '!=', CustomerPayment::STATUS_REVERSED)
            ->count();

        return [
            Stat::make("Today's Collections", '৳ ' . number_format((float) $todayTotal, 2))
                ->description("Confirmed payments today")
                ->color('success'),

            Stat::make("This Month", '৳ ' . number_format((float) $monthTotal, 2))
                ->description(Carbon::now()->format('F Y'))
                ->color('primary'),

            Stat::make('Pending Payments', $pendingCount)
                ->description('Not yet confirmed')
                ->color($pendingCount > 0 ? 'warning' : 'success'),
        ];
    }
}
