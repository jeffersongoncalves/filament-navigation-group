<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Navigation;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JeffersonGoncalves\Filament\NavigationGroup\Support\NavigationSortRegistry;

/**
 * Replicates filamentphp/filament#20525 (closed, never merged): unregistered
 * (ad hoc) navigation groups are given a tie-break weight from their items'
 * `NavigationItem::groupSort()`, instead of always sorting after every
 * registered group in first-discovery order.
 *
 * This is a full copy of the parent `get()` method (Filament v3 shape) with
 * two additions, marked below — everything else is unchanged so behaviour
 * stays identical to core when no `groupSort()`/`sort()` is ever set.
 */
class ExtendedNavigationManager extends NavigationManager
{
    /**
     * The parent constructor clones every panel-registered `NavigationItem`
     * before storing it (`clone $item`), which drops the `groupSort()` value
     * our macro keeps in a `WeakMap` keyed by object identity. Re-attach it
     * to the clones here, matched by array position (the same order the
     * parent's `array_map()` preserves).
     */
    public function __construct()
    {
        parent::__construct();

        $originalItems = $this->panel->getNavigationItems();
        $clonedItems = $this->getNavigationItems();

        foreach ($originalItems as $index => $originalItem) {
            if (! isset(NavigationSortRegistry::$itemGroupSort[$originalItem])) {
                continue;
            }

            $clonedItems[$index]->groupSort(NavigationSortRegistry::$itemGroupSort[$originalItem]);
        }
    }

    /**
     * @return array<NavigationGroup>
     */
    public function get(): array
    {
        if ($this->panel->hasNavigationBuilder()) {
            return $this->panel->buildNavigation();
        }

        if (! $this->isNavigationMounted) {
            $this->mountNavigation();
        }

        $groups = collect($this->getNavigationGroups());

        return collect($this->getNavigationItems())
            ->filter(fn (NavigationItem $item): bool => $item->isVisible())
            ->sortBy(fn (NavigationItem $item): int => $item->getSort())
            ->groupBy(fn (NavigationItem $item): string => $item->getGroup() ?? '')
            ->map(function (Collection $items, string $groupIndex) use ($groups): NavigationGroup {
                $parentItems = $items->groupBy(fn (NavigationItem $item): string => $item->getParentItem() ?? '');

                $items = $parentItems->get('', collect())
                    ->keyBy(fn (NavigationItem $item): string => $item->getLabel());

                $parentItems->except([''])->each(function (Collection $parentItemItems, string $parentItemLabel) use ($items) {
                    if (! $items->has($parentItemLabel)) {
                        return;
                    }

                    $items->get($parentItemLabel)->childItems($parentItemItems);
                });

                $items = $items->filter(fn (NavigationItem $item): bool => (filled($item->getChildItems()) || filled($item->getUrl())));

                if (blank($groupIndex)) {
                    return NavigationGroup::make()->items($items);
                }

                $registeredGroup = $groups
                    ->first(function (NavigationGroup|string $registeredGroup, string|int $registeredGroupIndex) use ($groupIndex) {
                        if ($registeredGroupIndex === $groupIndex) {
                            return true;
                        }

                        if ($registeredGroup === $groupIndex) {
                            return true;
                        }

                        if (! $registeredGroup instanceof NavigationGroup) {
                            return false;
                        }

                        return $registeredGroup->getLabel() === $groupIndex;
                    });

                if ($registeredGroup instanceof NavigationGroup) {
                    return $registeredGroup->items($items);
                }

                $group = NavigationGroup::make($registeredGroup ?? $groupIndex);

                // Addition (filamentphp/filament#20525): only unregistered
                // groups get a sort weight, derived from the highest
                // `groupSort()` set on any of their items.
                $groupSort = $items
                    ->map(fn (NavigationItem $item): ?int => $item->getGroupSort())
                    ->filter(fn (?int $sort): bool => $sort !== null)
                    ->max();

                if ($groupSort !== null) {
                    $group->sort($groupSort);
                }

                return $group->items($items);
            })
            ->filter(fn (NavigationGroup $group): bool => filled($group->getItems()))
            ->sortBy(function (NavigationGroup $group, ?string $groupIndex): int {
                if (blank($group->getLabel())) {
                    return -1;
                }

                $registeredGroups = $this->getNavigationGroups();

                $groupsToSearch = $registeredGroups;

                if (Arr::first($registeredGroups) instanceof NavigationGroup) {
                    $groupsToSearch = [
                        ...array_keys($registeredGroups),
                        ...array_map(fn (NavigationGroup $registeredGroup): string => $registeredGroup->getLabel(), array_values($registeredGroups)),
                    ];
                }

                $sort = array_search(
                    $groupIndex,
                    $groupsToSearch,
                );

                if ($sort === false) {
                    // Addition (filamentphp/filament#20525): break ties
                    // between unregistered groups using their `getSort()`
                    // instead of always returning `count($registeredGroups)`.
                    return count($registeredGroups) + $group->getSort();
                }

                return $sort;
            })
            ->all();
    }
}
