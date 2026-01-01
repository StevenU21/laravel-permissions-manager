<?php

use Deifhelt\LaravelPermissionsManager\Traits\HasPermissionCheck;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

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
    Config::set('permissions.super_admin_role', 'admin');

    $user = UserWithRoles::create(['email' => 'admin@example.com', 'name' => 'Admin', 'password' => 'password']);
    $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $user->assignRole($role);

    $policy = new PolicyWithTrait();

    // before() returns true if authorized, null if not (it never returns false for bypass)
    expect($policy->before($user, 'any-ability'))->toBeTrue();
});

it('does not authorize non-admin role by default', function () {
    Config::set('permissions.super_admin_role', 'admin');

    $user = UserWithRoles::create(['email' => 'user@example.com', 'name' => 'User', 'password' => 'password']);
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $user->assignRole($role);

    $policy = new PolicyWithTrait();

    expect($policy->before($user, 'any-ability'))->toBeNull();
});

it('authorizes custom string super admin role', function () {
    Config::set('permissions.super_admin_role', 'root');

    $user = UserWithRoles::create(['email' => 'root@example.com', 'name' => 'Root', 'password' => 'password']);
    $role = Role::create(['name' => 'root', 'guard_name' => 'web']);
    $user->assignRole($role);

    $policy = new PolicyWithTrait();

    expect($policy->before($user, 'any-ability'))->toBeTrue();
});

it('authorizes array of super admin roles', function () {
    Config::set('permissions.super_admin_role', ['developer', 'owner']);

    // Test Developer
    $dev = UserWithRoles::create(['email' => 'dev@example.com', 'name' => 'Dev', 'password' => 'password']);
    $dev->assignRole(Role::create(['name' => 'developer', 'guard_name' => 'web']));

    $policy = new PolicyWithTrait();
    expect($policy->before($dev, 'any-ability'))->toBeTrue();

    // Test Owner
    $owner = UserWithRoles::create(['email' => 'owner@example.com', 'name' => 'Owner', 'password' => 'password']);
    $owner->assignRole(Role::create(['name' => 'owner', 'guard_name' => 'web']));

    expect($policy->before($owner, 'any-ability'))->toBeTrue();
});

it('allows super admin via gate global bypass', function () {
    // 1. Configure Super Admin
    Config::set('permissions.super_admin_role', 'admin');

    // 2. Create User with that role (but NO permissions in DB)
    $user = UserWithRoles::create(['name' => 'Admin Gate', 'email' => 'gate@admin.com', 'password' => 'secret']);
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user->assignRole($role);

    // 3. Verify Gate::allows() returns true for ANY permission
    // Note: We need to register the specific gate definition or mock it if we wanted strict testing,
    // but the Gate::before callback should run regardless of whether the specific ability is defined
    // *IF* the ability is checked. However, Laravel usually checks 'before' callbacks first.

    // For this test to pass in a package testbench without full app booting, we rely on the implementation
    // in the ServiceProvider. 

    // We can simulate the check:
    Gate::define('test-check', fn() => false); // Always false normally

    // But for admin, it should be true
    expect(Gate::forUser($user)->allows('test-check'))->toBeTrue();

    // Even undefined permissions:
    // Note: Laravel might return false for undefined abilities depending on config, but Gate::before runs first.
    // Let's assert against true.
    expect(Gate::forUser($user)->allows('non-existent-permission'))->toBeTrue();
});
