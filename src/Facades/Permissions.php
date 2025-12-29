<?php

namespace Deifhelt\LaravelPermissionsManager\Facades;

use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Illuminate\Support\Facades\Facade;

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
