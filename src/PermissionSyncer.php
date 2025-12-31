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

        $timestamp = now();
        $upsertData = collect($flatPermissions)->map(fn($name) => [
            'name' => $name,
            'guard_name' => $guard,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->toArray();

        Permission::upsert($upsertData, ['name', 'guard_name'], ['updated_at']);

        $results = [];
        foreach ($rolesWithPermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);

            $role->syncPermissions($permissions);

            $results[$roleName] = count($permissions);
        }

        // Ensure Super Admin roles exist (even if they have no explicit permissions)
        $superAdminConfig = config('permissions.super_admin_role', 'admin');
        $superAdminRoles = is_array($superAdminConfig) ? $superAdminConfig : [$superAdminConfig];

        foreach ($superAdminRoles as $superAdminRoleName) {
            Role::firstOrCreate(['name' => $superAdminRoleName, 'guard_name' => $guard]);

            if (!isset($results[$superAdminRoleName])) {
                $results[$superAdminRoleName] = 'SUPER ADMIN';
            }
        }

        return [
            'permissions_count' => count($flatPermissions),
            'roles_processed' => $results,
        ];
    }
}
