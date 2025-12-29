<?php

namespace Deifhelt\LaravelPermissionsManager\Facades;

use Illuminate\Support\Facades\Facade;
use Deifhelt\LaravelPermissionsManager\PermissionManager;

/**
 * @see \Antigravity\Permissions\PermissionManager
 */
class Permissions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PermissionManager::class;
    }
}
