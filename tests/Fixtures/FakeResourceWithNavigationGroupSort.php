<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Tests\Fixtures;

use JeffersonGoncalves\Filament\NavigationGroup\Concerns\HasNavigationGroupSort;

class FakeResourceWithNavigationGroupSort extends FakeNavigationSource
{
    use HasNavigationGroupSort;
}
