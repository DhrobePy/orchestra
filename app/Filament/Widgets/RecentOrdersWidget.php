<?php

namespace App\Filament\Widgets;

use App\Models\CreditOrder;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Orders';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('order_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CreditOrder::statusLabel($state))
                    ->color(fn (string $state): string => CreditOrder::statusColor($state)),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'           => 'success',
                        'partially_paid' => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid'           => 'Paid',
                        'partially_paid' => 'Partial',
                        default          => 'Unpaid',
                    }),

                TextColumn::make('total')
                    ->label('Total (৳)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->alignRight(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25])
            ->striped();
    }

    protected function getQuery(): Builder
    {
        $q = CreditOrder::with('customer')->withoutTrashed();

        // Staff sees only their own orders
        if (filament()->getCurrentPanel()?->getId() === 'app') {
            $q->where('created_by', auth()->id());
        }

        return $q;
    }
}
