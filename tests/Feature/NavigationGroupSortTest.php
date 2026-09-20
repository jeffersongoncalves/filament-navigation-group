<?php

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

it('sorts unregistered navigation groups using NavigationItem::groupSort()', function (): void {
    // `Late Group`'s item has the lowest item `sort()`, so without
    // `groupSort()` it would win the accidental "first item wins" order
    // and appear before `Early Group`.
    Filament::getCurrentOrDefaultPanel()
        ->navigationItems([
            NavigationItem::make('Alpha')
                ->group('Late Group')
                ->groupSort(100)
                ->sort(1)
                ->url('#'),
            NavigationItem::make('Beta')
                ->group('Early Group')
                ->sort(2)
                ->url('#'),
        ]);

    $groupLabels = collect(Filament::getNavigation())
        ->map(fn (NavigationGroup $group): ?string => $group->getLabel())
        ->filter(fn (?string $label): bool => in_array($label, ['Late Group', 'Early Group'], strict: true))
        ->values()
        ->all();

    expect($groupLabels)->toBe(['Early Group', 'Late Group']);
});

it('leaves unregistered groups in discovery order when groupSort() is never set', function (): void {
    Filament::getCurrentOrDefaultPanel()
        ->navigationItems([
            NavigationItem::make('Alpha')
                ->group('First Discovered')
                ->sort(1)
                ->url('#'),
            NavigationItem::make('Beta')
                ->group('Second Discovered')
                ->sort(2)
                ->url('#'),
        ]);

    $groupLabels = collect(Filament::getNavigation())
        ->map(fn (NavigationGroup $group): ?string => $group->getLabel())
        ->filter(fn (?string $label): bool => in_array($label, ['First Discovered', 'Second Discovered'], strict: true))
        ->values()
        ->all();

    expect($groupLabels)->toBe(['First Discovered', 'Second Discovered']);
});

it('never lets a registered group be outranked by an unregistered group\'s sort weight', function (): void {
    // `getSort()` only ever breaks ties *among* unregistered groups (it's
    // added on top of `count($registeredGroups)`), so a registered group
    // keeps winning regardless of how low a reasonable `groupSort()` is.
    Filament::getCurrentOrDefaultPanel()
        ->navigationGroups(['Registered Group']);

    Filament::getCurrentOrDefaultPanel()
        ->navigationItems([
            NavigationItem::make('Alpha')
                ->group('Unregistered Group')
                ->groupSort(0)
                ->url('#'),
            NavigationItem::make('Beta')
                ->group('Registered Group')
                ->url('#'),
        ]);

    $groupLabels = collect(Filament::getNavigation())
        ->map(fn (NavigationGroup $group): ?string => $group->getLabel())
        ->filter(fn (?string $label): bool => in_array($label, ['Registered Group', 'Unregistered Group'], strict: true))
        ->values()
        ->all();

    expect($groupLabels)->toBe(['Registered Group', 'Unregistered Group']);
});
