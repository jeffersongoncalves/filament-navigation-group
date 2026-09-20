<?php

namespace JeffersonGoncalves\Filament\NavigationGroup\Tests;

use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use JeffersonGoncalves\Filament\NavigationGroup\NavigationGroupServiceProvider;
use JeffersonGoncalves\Filament\NavigationGroup\Tests\Fixtures\TestPanelProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            TestPanelProvider::class,
            NavigationGroupServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
