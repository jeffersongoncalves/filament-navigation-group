<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Tests\Fixtures;

use Filament\Navigation\NavigationItem;

/**
 * Stands in for `Filament\Resources\Resource` / `Filament\Pages\Page`: both
 * expose a static, parameterless `getNavigationItems(): array` that
 * `HasNavigationGroupSort::getNavigationItems()` calls via `parent::`.
 */
class FakeNavigationSource
{
    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make('Fake')
                ->group('Fake Group')
                ->url('#'),
        ];
    }
}
