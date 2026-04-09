<?php

namespace Hammadzafar05\MobileBottomNav;

use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MobileBottomNavServiceProvider extends PackageServiceProvider
{
    public static string $name = 'mobile-bottom-nav';

    public static string $viewNamespace = 'mobile-bottom-nav';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews(static::$viewNamespace)
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            [],
            $this->getAssetPackageName()
        );
    }

    protected function getAssetPackageName(): ?string
    {
        return 'hammadzafar05/mobile-bottom-nav';
    }
}
