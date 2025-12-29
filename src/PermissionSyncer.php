<?php

namespace Deifhelt\LaravelPermissionsManager;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSyncer
{
    /**
     * Synchronize permissions and roles in the database efficiently.
     * Optimized to avoid race conditions and N+1 queries.
     */
    public function execute(PermissionManager $manager, string $guard = 'web'): array
    {
        $flatPermissions = $manager->all();
        $rolesWithPermissions = $manager->getRolesWithPermissions();

        // 1. Bulk Insert/Update of Permissions (High Concurrency)
        $timestamp = now();
        $upsertData = collect($flatPermissions)->map(fn($name) => [
            'name' => $name,
            'guard_name' => $guard,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->toArray();

        // Single query for all permissions, ignores duplicates based on 'name' and 'guard_name'.
        // Validates that 'updated_at' is updated (required by upsert syntax, effectively a no-op logic wise if we just want to ensure existence, 
        // but keeps timestamps fresh).
        Permission::upsert($upsertData, ['name', 'guard_name'], ['updated_at']);

        // 2. Role Synchronization
        $results = [];
        foreach ($rolesWithPermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

            // Sync permissions for the role
            $role->syncPermissions($permissions);

            $results[$roleName] = count($permissions);
        }

        return [
            'permissions_count' => count($flatPermissions),
            'roles_processed' => $results,
        ];
    }
}
