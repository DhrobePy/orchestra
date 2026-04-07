<?php

namespace App\Filament\Resources\Sales\CreditOrderResource\Pages;

use App\Filament\Resources\Sales\CreditOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\CreditOrder;
use Illuminate\Database\Eloquent\Builder;

class ListCreditOrders extends ListRecords
{
    protected static string $resource = CreditOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => CreditOrderResource::canCreate()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders'),

            'needs_action' => Tab::make('Needs Action')
                ->badge(CreditOrder::whereIn('status', [
                    CreditOrder::STATUS_PENDING_APPROVAL,
                    CreditOrder::STATUS_ESCALATED,
                    CreditOrder::STATUS_CANCELLATION_REQUESTED,
                ])->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    CreditOrder::STATUS_PENDING_APPROVAL,
                    CreditOrder::STATUS_ESCALATED,
                    CreditOrder::STATUS_CANCELLATION_REQUESTED,
                ])),

            'draft' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CreditOrder::STATUS_DRAFT)),

            'approved' => Tab::make('Approved / In Production')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    CreditOrder::STATUS_APPROVED,
                    CreditOrder::STATUS_IN_PRODUCTION,
                    CreditOrder::STATUS_READY_TO_SHIP,
                ])),

            'shipped' => Tab::make('Shipped / Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    CreditOrder::STATUS_SHIPPED,
                    CreditOrder::STATUS_DELIVERED,
                ])),

            'unpaid' => Tab::make('Unpaid')
                ->badge(CreditOrder::where('payment_status', '!=', CreditOrder::PAYMENT_PAID)
                    ->whereIn('status', [CreditOrder::STATUS_SHIPPED, CreditOrder::STATUS_DELIVERED])
                    ->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('payment_status', '!=', CreditOrder::PAYMENT_PAID)
                    ->whereIn('status', [CreditOrder::STATUS_SHIPPED, CreditOrder::STATUS_DELIVERED])
                ),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CreditOrder::STATUS_CANCELLED)),
        ];
    }
}
