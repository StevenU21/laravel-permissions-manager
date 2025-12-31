<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Permissions
    |--------------------------------------------------------------------------
    | Define your resources here. The manager will generate CRUD actions
    | (read, create, update, destroy) automatically.
    */
    'permissions' => [
        // Define your resources here
    ],

    /*
    |--------------------------------------------------------------------------
    | Special Permissions
    |--------------------------------------------------------------------------
    | Permissions that don't fit the CRUD pattern or standard resources.
    */
    'special_permissions' => [
        // Define special permissions here
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles Definition
    |--------------------------------------------------------------------------
    */
    'roles' => [
        // Define your roles here with their permissions
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    | The role(s) that bypass all permission checks.
    | Can be a string or an array of strings.
    | Default: 'admin'
    */
    'super_admin_role' => 'admin',
];
