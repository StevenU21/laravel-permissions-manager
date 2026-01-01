<?php

use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Deifhelt\LaravelPermissionsManager\Tests\TestCase;

uses(TestCase::class);

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

it('returns permissions with labels in detailed format by default', function () {
    $manager = PermissionManager::make(['users']);

    // Mock translations
    app('translator')->addLines([
        'permissions.actions.create' => 'Create',
        'permissions.resources.users' => 'Users',
    ], 'es', 'permissions'); // Add namespace!

    app()->setLocale('es');

    $result = $manager->getPermissionsWithLabels(); // Default: flatten = false

    expect($result)->toBeArray();
    expect($result[0])->toHaveKeys(['name', 'label']);
});

it('returns permissions with labels in flattened format when requested', function () {
    $manager = PermissionManager::make(['users']);

    // Mock translations
    app('translator')->addLines([
        'permissions.actions.create' => 'Create',
        'permissions.resources.users' => 'Users',
    ], 'es', 'permissions');

    app()->setLocale('es');

    $result = $manager->getPermissionsWithLabels(flatten: true);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('create users');
});

it('groups permissions correctly for UI', function () {
    $manager = PermissionManager::make(['users', 'posts']); // create users, etc.

    // Mock permissions as objects (like Eloquent models)
    $permissions = collect($manager->all())->map(function ($name, $index) {
        return (object) ['id' => $index + 1, 'name' => $name];
    });

    // Mock translations
    app('translator')->addLines([
        'permissions.actions.create' => 'Create',
    ], 'es', 'permissions');
    app()->setLocale('es');

    // Select "create users" (id corresponding to it)
    $createUsersId = $permissions->firstWhere('name', 'create users')->id;

    $groups = $manager->buildPermissionGroups($permissions, null, [$createUsersId]);

    expect($groups)->toBeArray();

    // Check "users" group
    $usersGroup = collect($groups)->firstWhere('key', 'users');
    expect($usersGroup)->not->toBeNull();
    expect($usersGroup['title'])->toBe('Users');
    expect($usersGroup['permissions'])->toBeArray();

    // Check "create users" item
    $createUsersItem = collect($usersGroup['permissions'])->firstWhere('name', 'create users');
    expect($createUsersItem['checked'])->toBeTrue();
    // expect($createUsersItem['label'])->toContain('Create'); // Translation check
});

it('handles dot notation grouping correctly', function () {
    $manager = PermissionManager::make([]);

    // Dot notation inputs
    $permissions = collect([
        (object) ['id' => 1, 'name' => 'users.create'],
        (object) ['id' => 2, 'name' => 'users.view'],
        (object) ['id' => 3, 'name' => 'products.list'],
    ]);

    $groups = $manager->buildPermissionGroups($permissions);

    // Should have 'users' and 'products' groups
    $usersGroup = collect($groups)->firstWhere('key', 'users');
    expect($usersGroup)->not->toBeNull();
    expect(collect($usersGroup['permissions']))->toHaveCount(2);

    $productsGroup = collect($groups)->firstWhere('key', 'products');
    expect($productsGroup)->not->toBeNull();
});

it('builds flat permission items list', function () {
    $manager = PermissionManager::make(['users']);
    $permissions = collect($manager->all())->map(function ($name, $i) {
        return (object) ['id' => $i, 'name' => $name];
    });

    $items = $manager->buildPermissionItems($permissions);

    expect($items)->toHaveCount(4); // CRUD
    expect($items[0])->toHaveKeys(['id', 'name', 'label', 'labelSearch']);
});
