<?php

namespace Deifhelt\LaravelPermissionsManager\Commands;

use Illuminate\Console\Command;

class LaravelPermissionsManagerCommand extends Command
{
    public $signature = 'laravel-permissions-manager';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
