<?php

use Deifhelt\LaravelPermissionsManager\PermissionManager;

it('can compile crud permissions', function () {
    $manager = PermissionManager::make(['users']);
    $permissions = $manager->all();

    expect($permissions)->toContain('read users')
        ->toContain('create users')
        ->toContain('update users')
        ->toContain('destroy users')
        ->toHaveCount(4);
});

it('can compile multiple crud resources', function () {
    $manager = PermissionManager::make(['users', 'posts']);
    $permissions = $manager->all();

    expect($permissions)
        ->toHaveCount(8)
        ->toContain('create users')
        ->toContain('create posts');
});

it('can compile special permissions', function () {
    $manager = PermissionManager::make(
        [],
        ['system' => ['view-logs']]
    );

    $permissions = $manager->all();

    expect($permissions)
        ->toHaveCount(1)
        ->toContain('view-logs system');
});

it('merges crud and special permissions correctly for the same resource', function () {
    $manager = PermissionManager::make(
        ['users'],
        ['users' => ['ban']]
    );

    $permissions = $manager->all();

    // 4 CRUD + 1 Special = 5
    expect($permissions)
        ->toHaveCount(5)
        ->toContain('create users')
        ->toContain('ban users');
});

it('can assign all resource permissions to a role using just the resource name', function () {
    $manager = PermissionManager::make(
        ['users'],             // CRUD: read, create, update, destroy users
        ['users' => ['ban']]   // Special: ban
    )->withRoles([
                'manager' => ['users'] // Should get all 5 permissions
            ]);

    $roles = $manager->getRolesWithPermissions();

    expect($roles['manager'])
        ->toHaveCount(5)
        ->toContain('read users')
        ->toContain('create users')
        ->toContain('update users')
        ->toContain('destroy users')
        ->toContain('ban users');
});
