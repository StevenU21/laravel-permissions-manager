<?php

use Deifhelt\LaravelPermissionsManager\Facades\Permissions;
use Deifhelt\LaravelPermissionsManager\PermissionManager;

it('can access the permissions manager via the facade', function () {
    // Basic assertion that the facade works and returns an array of permissions
    expect(Permissions::make()->all())->toBeArray();
});

it('resolves the correct class from the facade', function () {
    // Verify that the facade root is indeed the PermissionManager class
    $manager = Permissions::getFacadeRoot();

    expect($manager)->toBeInstanceOf(PermissionManager::class);
});
