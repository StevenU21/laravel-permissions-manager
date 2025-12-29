<?php

use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Deifhelt\LaravelPermissionsManager\PermissionSyncer;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

it('performs bulk inserts efficiently', function () {
    // 1. Setup a large number of permissions
    $permissions = [];
    for ($i = 0; $i < 1000; $i++) {
        $permissions["resource_$i"] = ['create', 'read', 'update', 'delete'];
    }

    $manager = PermissionManager::make($permissions);

    // 2. Count permissions to be created
    // 1000 resources * 4 actions = 4000 permissions
    expect(count($manager->all()))->toBe(4000);

    // 3. Monitor queries
    DB::enableQueryLog();

    $syncer = new PermissionSyncer;
    $result = $syncer->execute($manager);

    $log = DB::getQueryLog();

    // We expect:
    // 1 query for upserting all permissions
    // 0 queries for roles (we defined none)
    // plus maybe some checks from Spatie package? strict upsert should be 1.
    // However, depending on driver, upsert might be splitting chunks.
    // SQLite might handle it differently, but we check it's O(1) conceptually, not O(N).
    // 4000 inserts one by one would be 4000 queries.
    // Bulk insert should be 1 or few.

    expect(count($log))->toBeLessThan(10);
    expect(Permission::count())->toBe(4000);
});

it('syncs roles and permissions correctly', function () {
    $manager = PermissionManager::make([
        'users' => ['view', 'edit'],
        'posts' => ['publish'],
    ])->withRoles([
        'admin' => '*',
        'editor' => [
            'users' => 'view',
            'posts' => 'publish',
        ],
    ]);

    $syncer = new PermissionSyncer;
    $result = $syncer->execute($manager);

    // Check Permissions
    // users: view, edit (2)
    // posts: publish (1)
    // total: 3
    expect(Permission::count())->toBe(3);

    // Check Roles
    $admin = Spatie\Permission\Models\Role::where('name', 'admin')->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasPermissionTo('users view'))->toBeTrue();
    expect($admin->hasPermissionTo('users edit'))->toBeTrue();
    expect($admin->hasPermissionTo('posts publish'))->toBeTrue();

    $editor = Spatie\Permission\Models\Role::where('name', 'editor')->first();
    expect($editor->hasPermissionTo('users view'))->toBeTrue();
    expect($editor->hasPermissionTo('users edit'))->toBeFalse();
    expect($editor->hasPermissionTo('posts publish'))->toBeTrue();
});
