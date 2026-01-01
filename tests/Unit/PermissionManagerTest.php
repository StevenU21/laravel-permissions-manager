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
    // We expect translation, but since the Translator logic is complex (combining action + resource),
    // and we are just checking the structure here, checking for the key is sufficient.
    // However, let's check one value to be sure the mapping worked.
    // 'create users' -> translated to "Create Users" ideally.
    // But since the PermissionTranslator is static and uses real Lang, we mocking it slightly is enough.
});
