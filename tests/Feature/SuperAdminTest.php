<?php

use Deifhelt\LaravelPermissionsManager\Traits\HasPermissionCheck;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

// -----------------------------------------------------------------------------
// Test Classes
// -----------------------------------------------------------------------------

class UserWithRoles extends Authenticatable
{
    use HasRoles;
    protected $table = 'users';
    protected $guarded = [];
    protected $guard_name = 'web';
}

class PolicyWithTrait
{
    use HasPermissionCheck;
}

// -----------------------------------------------------------------------------
// Tests
// -----------------------------------------------------------------------------

beforeEach(function () {
    // Migrate the database (Spatie tables + Users table)
    $this->loadLaravelMigrations(); // Creates 'users' table
    $this->artisan('migrate');
});

it('authorizes default admin role', function () {
    config()->set('permissions.super_admin_role', 'admin');

    $user = UserWithRoles::create(['email' => 'admin@example.com', 'name' => 'Admin', 'password' => 'password']);
    $role = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    $user->assignRole($role);

    $policy = new PolicyWithTrait();

    // before() returns true if authorized, null if not (it never returns false for bypass)
    expect($policy->before($user, 'any-ability'))->toBeTrue();
});

it('does not authorize non-admin role by default', function () {
    config()->set('permissions.super_admin_role', 'admin');

    $user = UserWithRoles::create(['email' => 'user@example.com', 'name' => 'User', 'password' => 'password']);
    $role = \Spatie\Permission\Models\Role::create(['name' => 'editor']);
    $user->assignRole($role);

    $policy = new PolicyWithTrait();

    expect($policy->before($user, 'any-ability'))->toBeNull();
});

it('authorizes custom string super admin role', function () {
    config()->set('permissions.super_admin_role', 'root');

    $user = UserWithRoles::create(['email' => 'root@example.com', 'name' => 'Root', 'password' => 'password']);
    $role = \Spatie\Permission\Models\Role::create(['name' => 'root']);
    $user->assignRole($role);

    $policy = new PolicyWithTrait();

    expect($policy->before($user, 'any-ability'))->toBeTrue();
});

it('authorizes array of super admin roles', function () {
    config()->set('permissions.super_admin_role', ['developer', 'owner']);

    // Test Developer
    $dev = UserWithRoles::create(['email' => 'dev@example.com', 'name' => 'Dev', 'password' => 'password']);
    $dev->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'developer']));

    $policy = new PolicyWithTrait();
    expect($policy->before($dev, 'any-ability'))->toBeTrue();

    // Test Owner
    $owner = UserWithRoles::create(['email' => 'owner@example.com', 'name' => 'Owner', 'password' => 'password']);
    $owner->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'owner']));

    expect($policy->before($owner, 'any-ability'))->toBeTrue();
});
