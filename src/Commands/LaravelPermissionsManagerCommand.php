<?php

namespace Deifhelt\LaravelPermissionsManager\Commands;

use Illuminate\Console\Command;
use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Deifhelt\LaravelPermissionsManager\PermissionSyncer;

class LaravelPermissionsManagerCommand extends Command
{
    public $signature = 'permissions:sync {--guard=web : The guard to use}';

    public $description = 'Sync permissions based on the config file definition';

    public function handle(PermissionManager $manager, PermissionSyncer $syncer): int
    {
        $this->info('Starting permission synchronization...');

        $guards = explode(',', $this->option('guard'));

        foreach ($guards as $guard) {
            $guard = trim($guard);
            $this->info("Syncing for guard: [{$guard}]");

            $stats = $syncer->execute($manager, $guard);

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Permissions Synced', $stats['permissions_count']],
                    ['Roles Processed', count($stats['roles_processed'])],
                ]
            );

            foreach ($stats['roles_processed'] as $role => $count) {
                $this->line(" - Role <comment>{$role}</comment>: {$count} permissions attached.");
            }

            $this->newLine();
        }

        $this->info("All done! Processed " . count($guards) . " guard(s).");

        return self::SUCCESS;
    }
}
