<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Concerns;

use Filament\Navigation\NavigationItem;

/**
 * Adds the `$navigationGroupSort` / `getNavigationGroupSort()` /
 * `navigationGroupSort()` triad from filamentphp/filament#20525 to a
 * `Resource` or `Page`, mirroring the existing `$navigationGroup` triad.
 *
 * Filament's own `getNavigationItems()` has no extension point, so this
 * trait overrides it: it calls the parent implementation (`Resource` or
 * `Page`, whichever class uses this trait) and stamps `->groupSort()` from
 * `getNavigationGroupSort()` onto every item it returns.
 */
trait HasNavigationGroupSort
{
    protected static ?int $navigationGroupSort = null;

    public static function getNavigationGroupSort(): ?int
    {
        return static::$navigationGroupSort;
    }

    public static function navigationGroupSort(?int $sort): void
    {
        static::$navigationGroupSort = $sort;
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return array_map(
            fn (NavigationItem $item): NavigationItem => $item->groupSort(static::getNavigationGroupSort()),
            parent::getNavigationItems(),
        );
    }
}
