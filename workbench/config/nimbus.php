<?php

return [
    'prefix' => 'demo',
    'default_application' => 'main',
    'allowed_envs' => ['testing', 'local', 'staging', 'production'],
    'nested_folders' => true,
    'snapshot' => (bool) env('NIMBUS_SNAPSHOT', false),

    // Base URL of the current host environment.
    'base_url' => env('NIMBUS_RELAY_ENDPOINT'),

    // Detected by app.url, then app.env, and merged into the current host.
    'environments' => [
        'staging' => [
            'name' => 'Staging',
            'base_url' => 'https://staging.example.com',
            'enabled' => false,
        ],
        'production' => [
            'name' => 'Production',
            'base_url' => 'https://example.com',
            'enabled' => false,
        ],
    ],

    'applications' => [
        'main' => [
            'name' => 'Demo API',
            'routes' => [
                'strategy' => 'auto_detect',
                'prefix' => '_demo',
                'versioned' => true,
            ],
            'auth' => [
                'guard' => 'web',
                'special' => [
                    'injector' => 'remember_me_cookie',
                ],
            ],
            'headers' => [
                'x-request-id' => '$uuid',
                'x-session-id' => '$uuid',
            ],
        ],
        'internal' => [
            'name' => 'Internal API',
            'routes' => [
                'strategy' => 'openapi',
                'prefix' => 'api',
                'versioned' => false,
                'openapi' => [
                    'files' => [
                        'default' => __DIR__.'/../openapi.yaml',
                    ],
                    'show_operation_id' => true,
                ],
            ],
            'auth' => [
                'guard' => 'web',
                'special' => [
                    'injector' => 'remember_me_cookie',
                ],
            ],
            'headers' => [
                'x-admin-token' => 'secret-admin-token',
            ],
        ],
    ],
];
