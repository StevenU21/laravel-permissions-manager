<?php

namespace Deifhelt\LaravelPermissionsManager\Tests\Feature;

use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Deifhelt\LaravelPermissionsManager\PermissionSyncer;
use Deifhelt\LaravelPermissionsManager\Tests\TestCase;
use Spatie\Permission\Models\Permission;

use PHPUnit\Framework\Attributes\Test;

class PermissionSyncerTest extends TestCase
{
    #[Test]
    public function it_syncs_permissions_to_database()
    {
        $manager = PermissionManager::make(['products']);
        $syncer = new PermissionSyncer();

        $syncer->execute($manager);

        $this->assertTrue(Permission::where('name', 'create products')->exists());
        $this->assertEquals(4, Permission::count());
    }

    #[Test]
    public function it_updates_timestamps_for_existing_permissions()
    {
        // Create initial permission
        Permission::create(['name' => 'read products', 'guard_name' => 'web']);
        $original = Permission::where('name', 'read products')->first();

        // Travel to future to ensure timestamp diff
        $this->travel(1)->hour();

        $manager = PermissionManager::make(['products']);
        $syncer = new PermissionSyncer();

        $syncer->execute($manager);

        $updated = Permission::where('name', 'read products')->first();

        // Use standard PHPUnit assertion instead of Pest expectation
        $this->assertTrue($updated->updated_at->gt($original->updated_at));
    }
}
