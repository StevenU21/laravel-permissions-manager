<?php

namespace Deifhelt\LaravelPermissionsManager;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PermissionManagerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-permissions')
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        // Singleton for the Manager (Builder)
        $this->app->singleton(PermissionManager::class, fn() => new PermissionManager());

        // Alias for Facade access
        $this->app->alias(PermissionManager::class, 'permissions');

        // Scoped/Singleton for the Syncer (stateless service usually, but singleton is fine)
        $this->app->singleton(PermissionSyncer::class, fn() => new PermissionSyncer());
    }
}
