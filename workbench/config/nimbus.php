<?php

return [
    'prefix' => 'demo',
    'default_application' => 'main',
    'allowed_envs' => ['testing', 'local', 'staging', 'production'],

    'applications' => [
        'main' => [
            'name' => 'Demo API',
            'routes' => [
                'strategy' => 'auto_detect',
                'prefix' => '_demo',
                'versioned' => true,
                'api_base_url' => env('NIMBUS_RELAY_ENDPOINT'),
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
                'api_base_url' => env('NIMBUS_RELAY_ENDPOINT'),
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
