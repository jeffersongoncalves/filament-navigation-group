<?php

use JeffersonGoncalves\Filament\NavigationGroup\Tests\Fixtures\FakeResourceWithNavigationGroupSort;

afterEach(function (): void {
    FakeResourceWithNavigationGroupSort::navigationGroupSort(null);
});

it('defaults getNavigationGroupSort() to null', function (): void {
    expect(FakeResourceWithNavigationGroupSort::getNavigationGroupSort())->toBeNull();
});

it('stores the value set via navigationGroupSort()', function (): void {
    FakeResourceWithNavigationGroupSort::navigationGroupSort(42);

    expect(FakeResourceWithNavigationGroupSort::getNavigationGroupSort())->toBe(42);
});

it('stamps groupSort() onto every navigation item it builds', function (): void {
    FakeResourceWithNavigationGroupSort::navigationGroupSort(42);

    $items = FakeResourceWithNavigationGroupSort::getNavigationItems();

    expect($items)->toHaveCount(1)
        ->and($items[0]->getGroupSort())->toBe(42);
});

it('stamps a null groupSort() when no navigationGroupSort() was set', function (): void {
    $items = FakeResourceWithNavigationGroupSort::getNavigationItems();

    expect($items[0]->getGroupSort())->toBeNull();
});
