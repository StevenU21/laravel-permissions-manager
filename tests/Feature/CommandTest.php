<?php

namespace Deifhelt\LaravelPermissionsManager\Tests\Feature;

use Deifhelt\LaravelPermissionsManager\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;

use PHPUnit\Framework\Attributes\Test;

class CommandTest extends TestCase
{
    #[Test]
    public function it_runs_permissions_sync_command()
    {
        Config::set('permissions.permissions', ['orders']);

        $this->artisan('permissions:sync')
            ->assertSuccessful();

        $this->assertTrue(Permission::where('name', 'create orders')->exists());
    }

    #[Test]
    public function it_accepts_guard_option()
    {
        Config::set('permissions.permissions', ['orders']);

        $this->artisan('permissions:sync', ['--guard' => 'api'])
            ->assertSuccessful();

        $this->assertTrue(Permission::where('name', 'create orders')->where('guard_name', 'api')->exists());
    }
    #[Test]
    public function it_creates_configured_super_admin_role()
    {
        // Define a custom super admin role
        Config::set('permissions.super_admin_role', 'sysadmin');

        $this->artisan('permissions:sync')
            ->assertSuccessful();

        // Assert the role exists
        $this->assertDatabaseHas('roles', ['name' => 'sysadmin']);

        // Assert it doesn't necessarily have permissions (unless assigned elsewhere)
        // Since we didn't define any permissions for it, it should have 0
        $role = \Spatie\Permission\Models\Role::findByName('sysadmin');
        $this->assertEquals(0, $role->permissions()->count());
    }
}
