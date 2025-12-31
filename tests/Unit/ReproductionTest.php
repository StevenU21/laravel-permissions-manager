<?php

use Deifhelt\LaravelPermissionsManager\PermissionManager;

it('collides special permissions with same action name but different resources', function () {
    $manager = PermissionManager::make(
        [],
        [
            'users' => ['ban'],
            'ips' => ['ban']
        ]
    );

    $permissions = $manager->all();

    expect($permissions)->toContain('ban users');
    expect($permissions)->toContain('ban ips');
    expect($permissions)->toHaveCount(2);
});
