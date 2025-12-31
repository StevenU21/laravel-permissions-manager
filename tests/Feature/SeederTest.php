<?php

namespace Deifhelt\LaravelPermissionsManager\Tests\Feature;

use Deifhelt\LaravelPermissionsManager\Tests\TestCase;
use Deifhelt\LaravelPermissionsManager\Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class SeederTest extends TestCase
{
    #[Test]
    public function it_creates_super_admin_role_via_seeder()
    {
        // Define a custom super admin role
        Config::set('permissions.super_admin_role', 'seeder_admin');

        // Run the seeder
        $this->seed(RolesAndPermissionsSeeder::class);

        // Assert the role exists
        $this->assertDatabaseHas('roles', ['name' => 'seeder_admin']);

        $role = Role::findByName('seeder_admin');
        $this->assertNotNull($role);
    }
}
