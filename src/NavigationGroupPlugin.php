<?php

namespace JeffersonGoncalves\Filament\NavigationGroup;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Registering this plugin on a panel is optional: the navigation group/item
 * sort weight (`NavigationGroup::sort()`, `NavigationItem::groupSort()`,
 * `$navigationGroupSort`) is enabled package-wide by
 * `NavigationGroupServiceProvider` as soon as the package is installed. This
 * class only exists so the plugin can be registered via `->plugins([...])`
 * like any other Filament plugin, for consumers who prefer that convention.
 */
class NavigationGroupPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-navigation-group';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): Plugin
    {
        return filament(app(static::class)->getId());
    }
}
