<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Crumbls\Layup\LayupPlugin;
use Sanzgrapher\DraggableModal\DraggableModalPlugin;
use BinaryBuilds\CommandRunner\CommandRunnerPlugin;
use Hammadzafar05\MobileBottomNav\MobileBottomNav;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('Sales')
                    ->icon('heroicon-o-shopping-cart'),

                NavigationGroup::make('Purchasing')
                    ->icon('heroicon-o-truck'),

                NavigationGroup::make('Products')
                    ->icon('heroicon-o-cube'),

                NavigationGroup::make('Settings')      ->icon('heroicon-o-cog-6-tooth'),

                NavigationGroup::make('Schema Builder')
                    ->icon('heroicon-o-rectangle-stack'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                LayupPlugin::make(),
                DraggableModalPlugin::make(),
                CommandRunnerPlugin::make()->navigationIcon(null),
                MobileBottomNav::make(),
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
            ])
            ->routes(function () {
                \Illuminate\Support\Facades\Route::get(
                    'dynamic/{table}',
                    \App\Filament\Resources\DynamicRecords\Pages\ListDynamicRecords::class
                )->name('dynamic.index');

                \Illuminate\Support\Facades\Route::get(
                    'dynamic/{table}/create',
                    \App\Filament\Resources\DynamicRecords\Pages\CreateDynamicRecord::class
                )->name('dynamic.create');

                \Illuminate\Support\Facades\Route::get(
                    'dynamic/{table}/{record}/edit',
                    \App\Filament\Resources\DynamicRecords\Pages\EditDynamicRecord::class
                )->name('dynamic.edit');
            });
    }
}