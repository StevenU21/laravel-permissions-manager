<?php

namespace Deifhelt\LaravelPermissionsManager\Tests;

use Deifhelt\LaravelPermissionsManager\PermissionManagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

class TestCase extends Orchestra
{
    /**
     * The latest test response.
     *
     * @var \Illuminate\Testing\TestResponse|null
     */
    public static $latestResponse = null;



    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
            PermissionManagerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');

        // In memory sqlite
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $migrationFile = __DIR__ . '/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';

        if (file_exists($migrationFile)) {
            $migration = include $migrationFile;
            $migration->up();
        }
    }
}
