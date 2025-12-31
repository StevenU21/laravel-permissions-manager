# Installation

## Requirements

-   **PHP**: 8.2 or higher
-   **Laravel**: 10.x, 11.x or 12.x

## Install via Composer

```bash
composer require deifhelt/laravel-permissions-manager
```

> This will automatically install `spatie/laravel-permission` as a dependency.

## Publish Configuration

```bash
php artisan vendor:publish --provider="Deifhelt\LaravelPermissionsManager\PermissionManagerServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

## Run Migrations

```bash
php artisan optimize:clear
php artisan migrate
```

## Setup User Model

Add the `HasRoles` trait to your User model:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

## Middleware Configuration (Laravel 11 & 12)

Register middleware aliases in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return back()->with('error', 'You do not have permission to perform this action.');
        });
    })
    ->create();
```
