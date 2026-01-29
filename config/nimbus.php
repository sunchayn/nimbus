<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nimbus UI Prefix
    |--------------------------------------------------------------------------
    |
    | This value defines the URI prefix for the Nimbus UI. The interface will
    | be accessible at {your-domain}/{prefix}. You may change this to integrate
    | Nimbus into a specific route namespace within your application.
    |
    */

    'prefix' => 'nimbus',

    /*
    |--------------------------------------------------------------------------
    | Default Application
    |--------------------------------------------------------------------------
    |
    | This value defines the default application that Nimbus will load when
    | no application is specified in the request.
    |
    */

    'default_application' => 'main',

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | Specifies in which environments Nimbus is enabled. It is recommended
    | to exclude Nimbus from production as some features, such as user
    | impersonation, pose security risks. Use caution when enabling in
    | sensitive environments.
    |
    */

    'allowed_envs' => ['local', 'staging'],

    /*
    |--------------------------------------------------------------------------
    | Application Configurations
    |--------------------------------------------------------------------------
    |
    | Defines the different applications that Nimbus supports. Each application
    | can have its own route, authentication, and headers configuration.
    |
    */

    'applications' => [
        'main' => [
            'name' => 'Main',

            /*
            |--------------------------------------------------------------------------
            | Route Configuration
            |--------------------------------------------------------------------------
            |
            | These options control how Nimbus identifies and registers application
            | routes. The route configuration determines which endpoints will be
            | analyzed, displayed, or interacted with by Nimbus.
            */

            'routes' => [
                /*
                |--------------------------------------------------------------------------
                | Route Extraction Strategy
                |--------------------------------------------------------------------------
                |
                | Defines how Nimbus discovers and extracts routes for the application.
                | Available strategies:
                |   - 'AutoDetect': Automatically extract routes from Laravel's route
                |                    registry (default). This is the recommended option
                |                    for most applications.
                |   - 'OpenAPI':     Load routes from OpenAPI specification files. This
                |                    requires the 'devizzent/cebe-php-openapi' package to be installed.
                |
                */

                'strategy' => \Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum::AutoDetect,

                /*
                |--------------------------------------------------------------------------
                | OpenAPI Configuration
                |--------------------------------------------------------------------------
                |
                | This section contains settings specific to the 'OpenAPI' route
                | extraction strategy. It is only required if the 'strategy' above
                | is set to RoutesProcessingStrategyEnum::OpenAPI.
                |
                */

                'openapi' => [
                    /*
                    |--------------------------------------------------------------------------
                    | OpenAPI Specification Files
                    |--------------------------------------------------------------------------
                    |
                    | Maps version identifiers to OpenAPI specification file paths.
                    |
                    | Example:
                    | 'files' => [
                    |     'v1' => base_path('docs/openapi-v1.yaml'),
                    |     'v2' => base_path('docs/openapi-v2.json'),
                    | ],
                    |
                    | For non-versioned APIs, use any key (e.g., 'default'):
                    | 'files' => [
                    |     'default' => base_path('docs/openapi.yaml'),
                    | ],
                    |
                    */

                    'files' => [
                        // 'v1' => base_path('docs/openapi-v1.yaml'),
                    ],

                    /*
                    |--------------------------------------------------------------------------
                    | Show Operation ID
                    |--------------------------------------------------------------------------
                    |
                    | If enabled, Nimbus will display the Operation ID from the OpenAPI
                    | specification instead of the short endpoint URI in the sidebar.
                    |
                    */

                    'show_operation_id' => false,
                ],

                /*
                |--------------------------------------------------------------------------
                | Route Prefix
                |--------------------------------------------------------------------------
                |
                | The prefix used to discover and filter the routes for inclusion in Nimbus.
                | Only routes whose URIs begin with this prefix will be loaded in the UI.
                | Adjust this value if your API endpoints use a different root segment.
                |
                */

                'prefix' => 'api',

                /*
                |--------------------------------------------------------------------------
                | Versioned Routes
                |--------------------------------------------------------------------------
                |
                | Determines whether the routes identified by the prefix should be
                | treated as versioned (for example: /api/v1/users). If enabled, Nimbus
                | automatically detects version segments and handles them separately
                | in the schema representation. Disable this if your routes are flat
                | or non-versioned.
                |
                */

                'versioned' => false,

                /*
                  |--------------------------------------------------------------------------
                  | API Base URL
                  |--------------------------------------------------------------------------
                  |
                  | This value defines the base URL that Nimbus will use when relaying
                  | API requests from the UI. It is useful in cases where your API is
                  | hosted on a different domain, port, or subpath than the UI itself.
                  |
                  | If left null, Nimbus will automatically use the same host and scheme
                  | as the incoming request that triggered the relay. This is the
                  | recommended default for most deployments where the API and UI share
                  | the same origin.
                  |
                  */

                'api_base_url' => null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Authentication Configuration
            |--------------------------------------------------------------------------
            |
            | Defines how Nimbus authenticates API requests when interacting with your
            | application routes. The authentication configuration determines which
            | Laravel guard is used and how special authentication modes—such as
            | “login as current user” or “impersonate user” are handled.
            |
            */

            'auth' => [

                /*
                |--------------------------------------------------------------------------
                | Authentication Guard
                |--------------------------------------------------------------------------
                |
                | The name of the Laravel authentication guard used for the api endpoints.
                | This guard must be the guard used to authenticate the requests to
                | the API endpoints from the prefix above (nimbus.routes.prefix).
                */

                'guard' => 'web',

                /*
                |--------------------------------------------------------------------------
                | Special Authentication
                |--------------------------------------------------------------------------
                |
                | These settings control Nimbus's advanced authentication modes,
                | such as impersonation or logging in as the current user. Each mode
                | modifies outgoing HTTP requests by injecting appropriate credentials
                | or tokens before they are sent.
                |
                */

                'special' => [

                    /*
                    |--------------------------------------------------------------------------
                    | Authentication Injector
                    |--------------------------------------------------------------------------
                    |
                    | Defines the injector class used to modify outgoing requests with
                    | authentication credentials. The class must implement the
                    | `SpecialAuthenticationInjectorContract` interface.
                    |
                    | Included implementations:
                    |   - RememberMeCookieInjector::class:
                    |       Forwards or generates a Laravel "remember me" cookie.
                    |   - TymonJwtTokenInjector::class:
                    |       Injects a Bearer token using the `tymon/jwt-aut` package.
                    |
                    | P.S. You may provide a custom implementation to support alternative authentication mechanisms.
                    */

                    'injector' => \Sunchayn\Nimbus\Modules\Relay\Authorization\Injectors\RememberMeCookieInjector::class,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Global Headers
            |--------------------------------------------------------------------------
            |
            | Define any global headers that should be applied to every Nimbus request.
            | Each header may be defined as either:
            |   - A value from GlobalHeaderGeneratorTypeEnum::class, or
            |   - A raw primitive value (string, integer, or boolean).
            |
            | Example:
            | 'headers' => [
            |     'X-Request-ID' => GlobalHeaderGeneratorTypeEnum::UUID,
            |     'X-App-Version' => '1.0.0',
            | ],
            |
            */

            'headers' => [
            /** @see \Sunchayn\Nimbus\Modules\Config\GlobalHeaderGeneratorTypeEnum */
            ],
        ],
    ],
];
