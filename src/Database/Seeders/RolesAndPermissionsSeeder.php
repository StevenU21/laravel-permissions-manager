<?php

namespace Deifhelt\LaravelPermissionsManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Deifhelt\LaravelPermissionsManager\PermissionSyncer;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(PermissionManager $manager, PermissionSyncer $syncer): void
    {
        $this->command->info('Seeding permissions...');

        $guards = ['web']; // Default guard

        // If the user wants to specify guards, they might need to extend this class
        // or we could check config/permissions.php for a default guard setting if it existed.
        // For now, mirroring the default behavior of the command which defaults to 'web'.
        // Ideally we might want to read this from config if possible or allow passing it.

        foreach ($guards as $guard) {
            $stats = $syncer->execute($manager, $guard);
            $this->command->info("Permissions synced for guard [{$guard}]: {$stats['permissions_count']} permissions.");
        }
    }
}
