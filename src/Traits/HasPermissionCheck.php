<?php

namespace Deifhelt\LaravelPermissionsManager\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Illuminate\Auth\Access\HandlesAuthorization;

trait HasPermissionCheck
{
    use HandlesAuthorization;

    /**
     * Pre-authorization check.
     * Grants full access to 'admin' role (or configured super admin).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  string  $ability
     * @return bool|null
     */
    public function before($user, $ability)
    {
        if (method_exists($user, 'hasRole')) {
            $superAdmin = config('permissions.super_admin_role', 'admin');

            if ($user->hasRole($superAdmin)) {
                return true;
            }
        }
    }

    /**
     * Check if the user has the specific permission and optionally check model ownership.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @param  string  $permission
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return bool
     * @throws \Spatie\Permission\Exceptions\UnauthorizedException
     */
    public function checkPermission($user, string $permission, ?Model $model = null): bool
    {
        if (method_exists($user, 'hasPermissionTo') && !$user->hasPermissionTo($permission)) {
            throw new UnauthorizedException(403);
        }

        if ($model && $user->id !== $model->user_id) {
            throw new UnauthorizedException(403);
        }

        return true;
    }
}
