<?php

use Deifhelt\LaravelPermissionsManager\Database\Seeders\RolesAndPermissionsSeeder;
use Deifhelt\LaravelPermissionsManager\PermissionManager;
use Deifhelt\LaravelPermissionsManager\PermissionSyncer;
use Illuminate\Console\Command;

it('has a run method that accepts manager and syncer', function () {
    $seeder = new RolesAndPermissionsSeeder();

    $reflection = new ReflectionClass($seeder);
    $method = $reflection->getMethod('run');
    $parameters = $method->getParameters();

    expect($method)->toBeInstanceOf(ReflectionMethod::class);
    expect($parameters)->toHaveCount(2);

    $param1Type = $parameters[0]->getType();
    $param2Type = $parameters[1]->getType();

    expect($param1Type)->toBeInstanceOf(ReflectionNamedType::class);
    expect($param2Type)->toBeInstanceOf(ReflectionNamedType::class);

    expect($param1Type->getName())->toBe(PermissionManager::class);
    expect($param2Type->getName())->toBe(PermissionSyncer::class);
});
