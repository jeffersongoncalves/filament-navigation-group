<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Support;

use Closure;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use WeakMap;

/**
 * Filament's `NavigationGroup` / `NavigationItem` classes are extended at
 * runtime via macros (see `NavigationGroupServiceProvider::packageRegistered()`)
 * instead of subclassing, since Filament instantiates them directly
 * (`NavigationGroup::make()` / `NavigationItem::make()`), leaving no
 * container binding to intercept.
 *
 * Macros can't declare new class properties, so the `sort`/`groupSort`
 * values set via those macros are kept here, keyed by object identity
 * (a `WeakMap` so entries are garbage-collected with their owning
 * `NavigationGroup`/`NavigationItem` instance).
 */
class NavigationSortRegistry
{
    /** @var WeakMap<NavigationGroup, int|Closure|null> */
    public static WeakMap $groupSort;

    /** @var WeakMap<NavigationItem, int|Closure|null> */
    public static WeakMap $itemGroupSort;

    public static function boot(): void
    {
        static::$groupSort = new WeakMap;
        static::$itemGroupSort = new WeakMap;
    }
}
