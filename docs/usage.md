# Usage & Internal Mechanics

## The Sync Process (`permissions:sync`)

The sync command is not just a loop; it is an optimized database operation designed to handle hundreds of permissions without slowing down your deployment pipeline.

```bash
php artisan permissions:sync
```

### 1. Database Optimization (Upsert)

Instead of checking if each permission exists one by one (which would cause N+1 queries), the `PermissionSyncer` uses the `upsert` method.

**Why this matters:**

-   **Performance**: It sends a single SQL query to insert all permissions.
-   **Idempotency**: If a permission already exists, it simply updates the `updated_at` timestamp. This confirms the permission is still active in your config.
-   **Atomic Operation**: The roles and permissions are synced in a streamlined flow.

### 2. Guard Handling

By default, Laravel applications use the `web` guard. However, for APIs or multi-guard setups, you must be explicit.

```bash
php artisan permissions:sync --guard=api
```

This forces all generated permissions and roles to be associated with the `api` guard in the `permissions` and `roles` tables.

### 3. Role Synchronization

After ensuring all permissions exist, the syncer processes roles:

1. It tries to find the role by name and guard.
2. If distinct, it creates it using `firstOrCreate`.
3. It calls `syncPermissions()` on the role model. This method detaches any permissions **not** in your config and attaches the new ones.

> **Result**: Your config file is the authoritative source. If you remove a permission from a role in the config, it will be removed from the database up on the next sync.

## Database Seeders and Continuous Integration

For environments where you cannot run Artisan commands interactively (like NativePHP, automated testing, or CI/CD), we provide a Seeder.

### Internal Logic of `RolesAndPermissionsSeeder`

The seeder is effectively a wrapper around the `PermissionSyncer`.

```php
// Database/Seeders/RolesAndPermissionsSeeder.php

public function run(PermissionManager $manager, PermissionSyncer $syncer): void
{
    // ...
    $syncer->execute($manager, $guard);
    // ...
}
```

It uses Dependency Injection to get the same Manager and Syncer classes used by the console command, ensuring 100% consistency between your CLI operations and your seeders.
