<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectIfSuperAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->colors(['primary' => Color::Emerald])
            ->brandName(config('app.name', 'Orchestra ERP'))
            ->navigationGroups([
                NavigationGroup::make('Sales')
                    ->icon('heroicon-o-shopping-cart'),

                NavigationGroup::make('Purchasing')
                    ->icon('heroicon-o-truck'),

                NavigationGroup::make('Products')
                    ->icon('heroicon-o-cube'),
            ])
            // Explicitly list only operational resources — no schema builder or settings
            ->resources([
                \App\Filament\Resources\Sales\CustomerResource::class,
                \App\Filament\Resources\Sales\CreditOrderResource::class,
                \App\Filament\Resources\Sales\CustomerPaymentResource::class,
                \App\Filament\Resources\Purchasing\SupplierResource::class,
                \App\Filament\Resources\Purchasing\PurchaseOrderResource::class,
                \App\Filament\Resources\Purchasing\GoodsReceivedNoteResource::class,
                \App\Filament\Resources\Purchasing\PurchasePaymentResource::class,
                \App\Filament\Resources\Products\ProductResource::class,
                \App\Filament\Resources\Products\ProductVariantResource::class,
            ])
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                \App\Filament\Widgets\OrdersSummaryWidget::class,
                \App\Filament\Widgets\PurchaseOrdersSummaryWidget::class,
                \App\Filament\Widgets\RecentOrdersWidget::class,
                \App\Filament\Widgets\PaymentsSummaryWidget::class,
                \App\Filament\Widgets\DynamicModulesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RedirectIfSuperAdmin::class,
            ]);
    }
}
