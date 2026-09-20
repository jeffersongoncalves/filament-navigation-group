<?php

namespace JeffersonGoncalves\Filament\NavigationGroup;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NavigationGroupServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-navigation-group')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
