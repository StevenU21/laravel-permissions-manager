<?php

namespace Deifhelt\LaravelPermissionsManager;

use Illuminate\Support\ServiceProvider;
use Deifhelt\LaravelPermissionsManager\Commands\LaravelPermissionsManagerCommand;
use Illuminate\Support\Facades\Gate;

class PermissionManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'permissions');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/permissions.php' => config_path('permissions.php'),
            ], 'permissions-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/permissions'),
            ], 'permissions-translations');

            $this->commands([
                LaravelPermissionsManagerCommand::class,
            ]);
        }

        // Global Super Admin Bypass
        // This ensures @can() and $user->can() work without DB permissions
        Gate::before(function ($user, $ability) {
            $superAdminConfig = config('permissions.super_admin_role', 'admin');
            $superAdminRoles = is_array($superAdminConfig) ? $superAdminConfig : [$superAdminConfig];

            if (method_exists($user, 'hasRole')) {
                foreach ($superAdminRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/permissions.php', 'permissions');

        $this->app->singleton(PermissionManager::class, function ($app) {
            $config = $app['config']['permissions'] ?? [];

            return (new PermissionManager(
                $config['permissions'] ?? [],
                $config['special_permissions'] ?? []
            ))->withRoles($config['roles'] ?? []);
        });

        $this->app->alias(PermissionManager::class, 'permissions');

        $this->app->singleton(PermissionSyncer::class, function () {
            return new PermissionSyncer();
        });

        $this->app->singleton(PermissionTranslator::class, function () {
            return new PermissionTranslator();
        });

        $this->app->alias(PermissionTranslator::class, 'permission.translator');
    }
}
