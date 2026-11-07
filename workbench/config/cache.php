<?php

// The array store lives for one PHP request, so a rate limiter on it never counts past the first hit.
// The file store keeps the hits between requests, which the demo throttled routes depend on.
return [
    'default' => env('CACHE_STORE', 'file'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'nimbus_workbench_cache_'),
];
