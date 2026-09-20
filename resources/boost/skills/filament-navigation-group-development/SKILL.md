---
name: filament-navigation-group-development
description: Sort unregistered Filament navigation groups and their items, including NavigationGroup::sort(), NavigationItem::groupSort(), and the HasNavigationGroupSort trait for Resources/Pages.
---

# Filament Navigation Group Development

## When to use this skill

Use this skill when:
- A navigation group created only via `->group('Label')` (never listed in `Panel::navigationGroups()`) renders in the wrong position
- You need to control the relative order of two or more unregistered navigation groups
- Working with `NavigationGroup::sort()`, `NavigationItem::groupSort()`, or the `HasNavigationGroupSort` trait

## Package Overview

- **Package**: `jeffersongoncalves/filament-navigation-group`
- **Namespace**: `JeffersonGoncalves\Filament\NavigationGroup`
- **Dependencies**: `filament/filament`, `spatie/laravel-package-tools:^1.14.0`
- **Service Provider**: `JeffersonGoncalves\Filament\NavigationGroup\NavigationGroupServiceProvider`
- **Note**: Registering `NavigationGroupPlugin` on a panel is optional — the behaviour is enabled package-wide by the service provider as soon as it's installed.

## Version Compatibility

| Branch | Filament | PHP |
|--------|----------|-----|
| 1.x | 3.x | ^8.1 |
| 2.x | 4.x | ^8.2 |
| 3.x | 5.x | ^8.2 |

## The problem this solves

Filament core has no way to control the render order of navigation groups
that aren't registered in `Panel::navigationGroups()`. An ad hoc group
(created only by `->group('Label')` on a resource, page, or navigation item)
always sorts after every registered group, tied at the same weight, ordered
only by first-discovery. [filamentphp/filament#20525](https://github.com/filamentphp/filament/pull/20525)
proposed fixing this upstream but was closed without being merged into any
Filament version — this package implements that PR's exact behaviour.

## Configuration

### `NavigationItem::groupSort()`

```php
use Filament\Navigation\NavigationItem;

NavigationItem::make('Tickets')
    ->group('Support')
    ->groupSort(100)
    ->url('/admin/tickets');
```

A group's effective sort weight is the **highest** `groupSort()` value set on
any of its items. The lower a group's weight, the earlier it renders,
relative to other unregistered groups only.

### `HasNavigationGroupSort` on a Resource

```php
use Filament\Resources\Resource;
use JeffersonGoncalves\Filament\NavigationGroup\Concerns\HasNavigationGroupSort;

class TicketResource extends Resource
{
    use HasNavigationGroupSort;

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationGroupSort = 100;
}
```

Or set it at runtime, mirroring `navigationGroup()`:

```php
TicketResource::navigationGroupSort(100);
```

### `HasNavigationGroupSort` on a Page

```php
use Filament\Pages\Page;
use JeffersonGoncalves\Filament\NavigationGroup\Concerns\HasNavigationGroupSort;

class ManageSettings extends Page
{
    use HasNavigationGroupSort;

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationGroupSort = 100;
}
```

### `NavigationGroup::sort()` directly

```php
use Filament\Navigation\NavigationGroup;

$panel->navigationGroups([
    NavigationGroup::make('Support'), // registered: array position always wins, sort() here is a no-op for ordering
]);
```

`sort()` on a `NavigationGroup` only has an effect when that exact group
instance is the one Filament builds for an *unregistered* group name inside
`NavigationManager::get()` — in practice, you almost always want
`NavigationItem::groupSort()` or `HasNavigationGroupSort` instead, since
those are set on the things you already control (items, resources, pages),
not on the ad hoc `NavigationGroup` object Filament creates internally.

## Semantics (exact, preserved from the original PR)

- Registering a group in `Panel::navigationGroups()` always wins — its array
  position is the only thing that decides where it renders. Sort weights only
  break ties **among groups you never registered**.
- `NavigationGroup::getSort()` defaults to `0` (not `-1`, unlike
  `NavigationItem::getSort()`), so a group that never gets a weight keeps
  today's exact relative order.
- `NavigationItem::getGroupSort()` defaults to `null` ("no opinion").

## Architecture

Filament instantiates `NavigationGroup` / `NavigationItem` directly (there's
no container binding to intercept), so this package adds `sort()` /
`getSort()` / `groupSort()` / `getGroupSort()` as macros in
`NavigationGroupServiceProvider::packageRegistered()`. The values set through
those macros are kept in `Support\NavigationSortRegistry`, two `WeakMap`s
keyed by object identity (macros can't declare new class properties).

`Filament\Navigation\NavigationManager` *is* resolved from the container
(`$this->app->scoped(NavigationManager::class, ...)` in Filament's own
service provider), so `Navigation\ExtendedNavigationManager` overrides its
`get()` method — the one that groups and orders navigation items — and is
registered via `$this->app->extend(NavigationManager::class, ...)`.

`NavigationManager`'s constructor clones every panel-registered
`NavigationItem` before storing it, which would otherwise drop the
`groupSort()` value tracked in the `WeakMap` (a clone has a new object
identity). `ExtendedNavigationManager::__construct()` re-attaches it to the
clones, matched by array position.

`Concerns\HasNavigationGroupSort` adds `$navigationGroupSort` /
`getNavigationGroupSort()` / `navigationGroupSort()` to a `Resource` or
`Page`, and overrides `getNavigationItems()` to call
`parent::getNavigationItems()` and stamp `->groupSort()` onto every item it
returns — the only way to hook that method without patching Filament core,
since both classes expose it as a plain static method with no extension
point.

## Troubleshooting

### An unregistered group's order didn't change

**Cause**: The group is actually registered — check `Panel::navigationGroups()`
in every panel provider and plugin. Registered groups always keep their array
position regardless of `sort()`/`groupSort()`.

**Solution**: Either add explicit ordering to `navigationGroups()` directly,
or remove the group from that array if you want `groupSort()` to control it.

### `HasNavigationGroupSort::getNavigationItems()` throws or is never called

**Cause**: The trait overrides `getNavigationItems()` and calls
`parent::getNavigationItems()` — if the class using the trait doesn't
actually extend `Resource` or `Page` (or an intermediate class that defines
`getNavigationItems()`), there's no parent implementation to call.

**Solution**: Only `use HasNavigationGroupSort;` inside a class that extends
`Filament\Resources\Resource` or `Filament\Pages\Page`.

### `NavigationGroup::sort()` / `NavigationItem::groupSort()` "does not exist"

**Cause**: `NavigationGroupServiceProvider` never booted — usually because
the package isn't actually installed/discovered, or a test's
`getPackageProviders()` doesn't list it.

**Solution**: Confirm `composer require jeffersongoncalves/filament-navigation-group`
ran, and that `NavigationGroupServiceProvider::class` is present in your
test's `getPackageProviders()`.
