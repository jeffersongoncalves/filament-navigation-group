## Filament Navigation Group

Gives unregistered ("ad hoc") Filament navigation groups a sort weight, replicating [filamentphp/filament#20525](https://github.com/filamentphp/filament/pull/20525) — a Filament core PR that was closed without being merged.

### Installation

@verbatim
<code-snippet name="Install the plugin" lang="bash">
composer require jeffersongoncalves/filament-navigation-group
</code-snippet>
@endverbatim

Nothing else to configure — installing the package is enough. There's no `Plugin` class you have to register on a panel.

### Sort an item's unregistered group

@verbatim
<code-snippet name="NavigationItem::groupSort()" lang="php">
use Filament\Navigation\NavigationItem;

NavigationItem::make('Tickets')
    ->group('Support')
    ->groupSort(100)
    ->url('/admin/tickets');
</code-snippet>
@endverbatim

### Sort a Resource/Page's unregistered group

@verbatim
<code-snippet name="HasNavigationGroupSort on a Resource" lang="php">
use Filament\Resources\Resource;
use JeffersonGoncalves\Filament\NavigationGroup\Concerns\HasNavigationGroupSort;

class TicketResource extends Resource
{
    use HasNavigationGroupSort;

    protected static string|\UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationGroupSort = 100;
}
</code-snippet>
@endverbatim

### Features
- `NavigationGroup::sort(int|Closure|null $sort)` / `getSort(): int` — defaults to `0`.
- `NavigationItem::groupSort(int|Closure|null $sort)` / `getGroupSort(): ?int` — defaults to `null` (no opinion).
- `HasNavigationGroupSort` trait — adds `$navigationGroupSort` / `getNavigationGroupSort()` / `navigationGroupSort()` to a `Resource` or `Page`, mirroring the existing `$navigationGroup` triad.

### Best Practices
- Registering a group in `Panel::navigationGroups()` always wins — sort weights only break ties **among groups you never registered**.
- Leave `groupSort()`/`$navigationGroupSort` unset to keep today's exact behaviour (first-discovery order, after every registered group).
- A group's effective weight is the **highest** `groupSort()` set on any of its items.
