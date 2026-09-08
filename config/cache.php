<?php

return [

    'default' => env('CACHE_STORE', 'database'),

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION'),
            'table' => 'cache',
            'lock_connection' => env('DB_CONNECTION'),
            'lock_table' => null,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
            'lock_lottery' => [2, 1],
        ],

    ],

    'prefix' => env('CACHE_PREFIX', 'plataforma_formacion_cache'),

];
