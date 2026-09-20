<div class="filament-hidden">

![Filament Navigation Group](https://raw.githubusercontent.com/jeffersongoncalves/filament-navigation-group/3.x/art/jeffersongoncalves-filament-navigation-group.png)

</div>

# Filament Navigation Group

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/filament-navigation-group.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-navigation-group)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-navigation-group/tests.yml?branch=3.x&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/filament-navigation-group/actions?query=workflow%3ATests+branch%3A3.x)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-navigation-group/pint.yml?branch=3.x&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/filament-navigation-group/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3A3.x)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/filament-navigation-group.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-navigation-group)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/filament-navigation-group.svg?style=flat-square)](LICENSE.md)

Filament has no way to control the render order of navigation groups that
aren't explicitly registered via `Panel::navigationGroups()` — an ad hoc group
(created just by calling `->group('Label')` on a resource, page, or
navigation item) always sorts after every registered group, tied at the same
weight, ordered only by whichever one it first ran into. A Filament PR fixing
this ([filamentphp/filament#20525](https://github.com/filamentphp/filament/pull/20525))
was closed without being merged into any version. This package implements
that PR's exact behaviour as a standalone plugin.

## Compatibility

| Plugin Version | Filament Version |
|-----------------|------------------|
| [1.x](https://github.com/jeffersongoncalves/filament-navigation-group/tree/1.x) | 3.x |
| [2.x](https://github.com/jeffersongoncalves/filament-navigation-group/tree/2.x) | 4.x |
| [3.x](https://github.com/jeffersongoncalves/filament-navigation-group/tree/3.x) | 5.x |

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/filament-navigation-group:"^3.0"
```

Nothing else to do — installing the package is enough. There's no `Plugin`
class you have to register on a panel (`NavigationGroupPlugin` exists purely
for consumers who like registering every package via `->plugins([...])`; it
has nothing to configure).

## Usage

### Sorting an ad hoc group registered via a closure

```php
use Filament\Navigation\NavigationItem;

$panel->navigationGroups([
    // Registered groups always keep their array position — sort() here
    // only matters for groups you *don't* list in navigationGroups().
]);

$panel->navigationItems([
    NavigationItem::make('Roles')
        ->group('Support')
        ->groupSort(100)
        ->url('/admin/roles'),
]);
```

### `NavigationItem::groupSort()`

Set on any item whose `group()` isn't registered in `navigationGroups()`. The
highest `groupSort()` among a group's items becomes that group's tie-break
weight — the lower the weight, the earlier the group renders relative to
other unregistered groups:

```php
NavigationItem::make('Tickets')
    ->group('Support')
    ->groupSort(100)
    ->url('/admin/tickets');
```

### `$navigationGroupSort` / `navigationGroupSort()` on a Resource or Page

Mirrors the existing `$navigationGroup` / `navigationGroup()` pair. Add the
`HasNavigationGroupSort` trait to opt in:

```php
use Filament\Resources\Resource;
use JeffersonGoncalves\Filament\NavigationGroup\Concerns\HasNavigationGroupSort;

class TicketResource extends Resource
{
    use HasNavigationGroupSort;

    protected static string|\UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationGroupSort = 100;
}
```

Or set it at runtime:

```php
TicketResource::navigationGroupSort(100);
```

The same trait works on a `Page`:

```php
use Filament\Pages\Page;
use JeffersonGoncalves\Filament\NavigationGroup\Concerns\HasNavigationGroupSort;

class ManageSettings extends Page
{
    use HasNavigationGroupSort;

    protected static string|\UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationGroupSort = 100;
}
```

## What this does and doesn't affect

- Registering a group in `navigationGroups()` always wins — its position in
  that array is the only thing that decides where it renders. `sort()` /
  `groupSort()` only break ties **among groups you never registered**.
- An unregistered group with no `groupSort()` anywhere renders exactly where
  it does today: after every registered group, in first-discovery order.
- `NavigationGroup::getSort()` defaults to `0`, so opting out of the feature
  changes nothing.

## How it works

Filament instantiates `NavigationGroup` / `NavigationItem` directly (there's
no container binding to intercept), so `sort()` / `getSort()` /
`groupSort()` / `getGroupSort()` are added as
[macros](https://filamentphp.com/docs/support/macros) in
`NavigationGroupServiceProvider`. `Filament\Navigation\NavigationManager` *is*
resolved out of the container, so its `get()` method — the one that builds
and orders navigation groups — is overridden via `ExtendedNavigationManager`,
registered with `$this->app->extend(NavigationManager::class, ...)`.

## Testing

```bash
composer test
composer analyse
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
