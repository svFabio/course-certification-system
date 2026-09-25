<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Team;

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Model
    |--------------------------------------------------------------------------
    */

    'permission_model' => Permission::class,

    /*
    |--------------------------------------------------------------------------
    | Role Model
    |--------------------------------------------------------------------------
    */

    'role_model' => Role::class,

    /*
    |--------------------------------------------------------------------------
    | Permission Dependency
    |--------------------------------------------------------------------------
    */

    'dependencies' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache_store' => env('permission_cache_store', 'database'),

    'cache_prefix' => 'spatie_permission',

    'cache_lifetime_seconds' => env('permission_cache_lifetime', 43200),

    /*
    |--------------------------------------------------------------------------
    | Teams (not used)
    |--------------------------------------------------------------------------
    */

    'teams' => false,

    'team_class' => Team::class,

    /*
    |--------------------------------------------------------------------------
    | Register Permission Check Method
    |--------------------------------------------------------------------------
    */

    'register_permission_check_method' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Middleware
    |--------------------------------------------------------------------------
    */

    'register_route_middleware' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Blade Directives
    |--------------------------------------------------------------------------
    */

    'register_blade_directives' => true,

    /*
    |--------------------------------------------------------------------------
    | Model Resources
    |--------------------------------------------------------------------------
    */

    'model_resources' => [
        'role_resource' => [
            'short' => 'role',
            'display' => 'Role',
            'display_plural' => 'Roles',
        ],
        'permission_resource' => [
            'short' => 'permission',
            'display' => 'Permission',
            'display_plural' => 'Permissions',
        ],
    ],
];
