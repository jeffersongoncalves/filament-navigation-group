<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Navigation;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JeffersonGoncalves\Filament\NavigationGroup\Support\NavigationSortRegistry;
use UnitEnum;

/**
 * Replicates filamentphp/filament#20525 (closed, never merged): unregistered
 * (ad hoc) navigation groups are given a tie-break weight from their items'
 * `NavigationItem::groupSort()`, instead of always sorting after every
 * registered group in first-discovery order.
 *
 * This is a full copy of the parent `get()` method (Filament v4/v5 shape,
 * identical in both) with two additions, marked below — everything else is
 * unchanged so behaviour stays identical to core when no `groupSort()`/
 * `sort()` is ever set.
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
            ->map(function (NavigationItem $item): NavigationItem {
                // Addition (filamentphp/filament#20525): the parent clones
                // every item here too, which would otherwise drop the
                // `groupSort()` value a second time (see the constructor
                // override above for the first clone boundary).
                $clone = clone $item;

                if (isset(NavigationSortRegistry::$itemGroupSort[$item])) {
                    $clone->groupSort(NavigationSortRegistry::$itemGroupSort[$item]);
                }

                return $clone;
            })
            ->filter(fn (NavigationItem $item): bool => $item->isVisible())
            ->sortBy(fn (NavigationItem $item): int => $item->getSort())
            ->groupBy(function (NavigationItem $item): string {
                $group = $item->getGroup();

                return serialize($group);
            })
            ->map(function (Collection $items, string $groupIndex) use ($groups): NavigationGroup {
                $parentItems = $items->groupBy(fn (NavigationItem $item): string => $item->getParentItem() ?? '');

                $items = $parentItems->get('', collect());

                $parentItems->except([''])->each(function (Collection $parentItemItems, string $parentItemKey) use ($items): void {
                    $parent = $items->first(
                        fn (NavigationItem $item): bool => $item->getKey() === $parentItemKey || $item->getLabel() === $parentItemKey
                    );

                    if (! $parent) {
                        return;
                    }

                    $mergedChildren = collect($parent->getChildItems())
                        ->merge($parentItemItems)
                        ->sortBy(fn (NavigationItem $item): int => $item->getSort())
                        ->values();

                    $parent->childItems($mergedChildren);
                });

                $items = $items->filter(fn (NavigationItem $item): bool => (filled($item->getChildItems()) || filled($item->getUrl())));

                $groupName = unserialize($groupIndex);

                if (blank($groupName)) {
                    return NavigationGroup::make()->items($items);
                }

                $groupEnum = null;

                if ($groupName instanceof UnitEnum) {
                    $groupEnum = $groupName;
                    $groupName = $groupEnum->name;
                }

                $registeredGroup = $groups
                    ->first(function (NavigationGroup|string $registeredGroup, string|int $registeredGroupIndex) use ($groupName) {
                        if ($registeredGroupIndex === $groupName) {
                            return true;
                        }

                        if ($registeredGroup === $groupName) {
                            return true;
                        }

                        if (! $registeredGroup instanceof NavigationGroup) {
                            return false;
                        }

                        return $registeredGroup->getLabel() === $groupName;
                    });

                if ($registeredGroup instanceof NavigationGroup) {
                    return $registeredGroup->items($items);
                }

                $group = NavigationGroup::make($registeredGroup ?? $groupName);

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

                if ($groupEnum instanceof HasLabel) {
                    $group->label($groupEnum->getLabel());
                }

                if ($groupEnum instanceof HasIcon) {
                    $group->icon($groupEnum->getIcon());
                }

                return $group->items($items);
            })
            ->filter(fn (NavigationGroup $group): bool => filled($group->getItems()))
            ->pipe(function (Collection $groupsCollection): Collection {
                $registeredGroups = $this->getNavigationGroups();

                $groupsToSearch = $registeredGroups;

                if (Arr::first($registeredGroups) instanceof NavigationGroup) {
                    $groupsToSearch = [
                        ...array_keys($registeredGroups),
                        ...array_map(fn (NavigationGroup $registeredGroup): string => $registeredGroup->getLabel(), array_values($registeredGroups)),
                    ];
                }

                return $groupsCollection->sortBy(function (NavigationGroup $group, ?string $groupIndex) use ($registeredGroups, $groupsToSearch): int {
                    if (blank($group->getLabel())) {
                        return -1;
                    }

                    $groupName = unserialize($groupIndex);
                    $groupEnum = null;

                    if ($groupName instanceof UnitEnum) {
                        $groupEnum = $groupName;
                        $groupName = $groupEnum->name;
                    }

                    $sort = array_search(
                        $groupName,
                        $groupsToSearch,
                    );

                    if ($groupEnum) {
                        $enumCaseSort = array_search($groupEnum, $groupEnum::cases());
                        $sort = ($enumCaseSort !== false) ? $enumCaseSort : $sort;
                    }

                    if ($sort === false) {
                        // Addition (filamentphp/filament#20525): break ties
                        // between unregistered groups using their `getSort()`
                        // instead of always returning `count($registeredGroups)`.
                        return count($registeredGroups) + $group->getSort();
                    }

                    return $sort;
                });
            })
            ->all();
    }
}
