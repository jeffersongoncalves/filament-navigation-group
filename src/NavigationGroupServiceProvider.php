<?php

namespace JeffersonGoncalves\Filament\NavigationGroup;

use Closure;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use JeffersonGoncalves\Filament\NavigationGroup\Navigation\ExtendedNavigationManager;
use JeffersonGoncalves\Filament\NavigationGroup\Support\NavigationSortRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NavigationGroupServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('filament-navigation-group');
    }

    public function packageRegistered(): void
    {
        NavigationSortRegistry::boot();

        $this->registerMacros();

        $this->app->extend(
            NavigationManager::class,
            fn (): ExtendedNavigationManager => new ExtendedNavigationManager,
        );
    }

    protected function registerMacros(): void
    {
        NavigationGroup::macro(
            'sort',
            function (int|Closure|null $sort): NavigationGroup {
                /** @var NavigationGroup $this */
                NavigationSortRegistry::$groupSort[$this] = $sort;

                return $this;
            },
        );

        NavigationGroup::macro(
            'getSort',
            function (): int {
                /** @var NavigationGroup $this */
                return $this->evaluate(NavigationSortRegistry::$groupSort[$this] ?? null) ?? 0;
            },
        );

        NavigationItem::macro(
            'groupSort',
            function (int|Closure|null $sort): NavigationItem {
                /** @var NavigationItem $this */
                NavigationSortRegistry::$itemGroupSort[$this] = $sort;

                return $this;
            },
        );

        NavigationItem::macro(
            'getGroupSort',
            function (): ?int {
                /** @var NavigationItem $this */
                return $this->evaluate(NavigationSortRegistry::$itemGroupSort[$this] ?? null);
            },
        );
    }
}
