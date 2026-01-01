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

it('proxies the translate method correctly', function () {
    // This confirms the new Permissions::translate() method works
    // We expect it to return a string (either the translation or the key itself)
    $result = Permissions::translate('create users');

    expect($result)->toBeString();
    // Default fallback is the key, unless translations are loaded
    expect($result)->not->toBeEmpty();
});
