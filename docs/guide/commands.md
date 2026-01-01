# Commands & Syncing

## The Sync Process (`permissions:sync`)

The sync command is an optimized database operation designed to handle hundreds of permissions without slowing down your deployment pipeline.

```bash
php artisan permissions:sync
```

### Internal Mechanics

1.  **Direct Database Upsert**: Instead of checking each permission one by one (N+1), it uses `upsert`. This means one SQL query to insert/update hundreds of permissions.
2.  **Cleanup**: It syncs roles by detaching any permissions that are **no longer** in your config file. Your config file is the Source of Truth.

### Guard Handling

For APIs or multi-guard setups, you must be explicit.

```bash
php artisan permissions:sync --guard=api
```

This forces all generated permissions and roles to be associated with the `api` guard.

## Database Seeders (CI/CD)

For environments where you cannot run Artisan commands interactively (like NativePHP, automated testing, or CI/CD), we provide a Seeder.

Add the `RolesAndPermissionsSeeder` to your `DatabaseSeeder`:

```php
use Deifhelt\LaravelPermissionsManager\Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
```

The seeder uses the exact same `PermissionSyncer` class as the command, ensuring 100% logic consistency.
