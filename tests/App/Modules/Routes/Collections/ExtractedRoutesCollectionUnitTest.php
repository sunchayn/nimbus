<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Collections;

use Error;
use Generator;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

#[CoversClass(ExtractedRoutesCollection::class)]
class ExtractedRoutesCollectionUnitTest extends TestCase
{
    #[DataProvider('covertToFrontendArrayDataProvider')]
    public function test_it_converts_to_frontend_array(
        array $items,
        array $expected,
    ): void {
        // Arrange

        $collection = new ExtractedRoutesCollection($items);

        // Act

        $output = $collection->toFrontendArray(shouldNestFolders: true);

        // Assert

        $output = $this->replaceTraceWithPlaceholder($output);

        $this->assertSame(
            $expected,
            $output,
        );
    }

    public function test_it_keeps_one_folder_per_resource_when_folders_are_not_nested(): void
    {
        // Arrange

        $collection = new ExtractedRoutesCollection([
            new ExtractedRoute(
                uri: new Endpoint(version: 'v1', resource: 'admin', value: '/api/admin/billing/invoices/list'),
                methods: ['GET'],
                schema: Schema::empty(),
            ),
            new ExtractedRoute(
                uri: new Endpoint(version: 'v1', resource: 'admin', value: '/api/admin/dashboard'),
                methods: ['GET'],
                schema: Schema::empty(),
            ),
        ]);

        // Act

        $output = $collection->toFrontendArray(shouldNestFolders: false);

        // Assert

        $this->assertCount(1, $output['v1']);
        $this->assertSame('admin', $output['v1'][0]['name']);
        $this->assertSame([], $output['v1'][0]['children']);
        $this->assertSame(
            ['admin/billing/invoices/list', 'admin/dashboard'],
            array_column($output['v1'][0]['routes'], 'shortUri'),
        );
    }

    public static function covertToFrontendArrayDataProvider(): Generator
    {
        yield 'Empty array' => [
            'items' => [],
            'expected' => [],
        ];

        yield 'Filled array' => [
            'items' => [
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'users',
                        value: '/api/users',
                    ),
                    methods: ['GET'],
                    schema: Schema::empty(),
                    keywords: ['/api/users', '/users'],
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'users',
                        value: '/api/users',
                    ),
                    methods: ['POST'],
                    schema: Schema::empty(),
                    keywords: ['/api/users', '/users'],
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'posts',
                        value: '/api/posts',
                    ),
                    methods: ['POST'],
                    schema: new Schema(
                        properties: [
                            new StringSchemaProperty(
                                name: 'type',
                            ),
                        ],
                    ),
                    keywords: ['/api/posts', '/posts'],
                ),
            ],
            'expected' => [
                'v1' => [
                    [
                        'name' => 'posts',
                        'path' => 'posts',
                        'routes' => [
                            [
                                'uri' => '/api/posts',
                                'shortUri' => 'posts',
                                'methods' => ['POST'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['/api/posts', '/posts'],
                            ],
                        ],
                        'children' => [],
                    ],
                    [
                        'name' => 'users',
                        'path' => 'users',
                        'routes' => [
                            [
                                'uri' => '/api/users',
                                'shortUri' => 'users',
                                'methods' => ['GET'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['/api/users', '/users'],
                            ],
                            [
                                'uri' => '/api/users',
                                'shortUri' => 'users',
                                'methods' => ['POST'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['/api/users', '/users'],
                            ],
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];

        yield 'Filled array with errors' => [
            'items' => [
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'users',
                        value: '/api/users',
                    ),
                    methods: ['GET'],
                    schema: Schema::empty(),
                    keywords: ['/api/users', '/users'],
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'users',
                        value: '/api/users',
                    ),
                    methods: ['POST'],
                    schema: new Schema(
                        properties: [],
                        extractionError: new RulesExtractionError(
                            throwable: new Error,
                        )
                    ),
                    keywords: ['/api/users', '/users'],
                ),
            ],
            'expected' => [
                'v1' => [
                    [
                        'name' => 'users',
                        'path' => 'users',
                        'routes' => [
                            [
                                'uri' => '/api/users',
                                'shortUri' => 'users',
                                'methods' => ['GET'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['/api/users', '/users'],
                            ],
                            [
                                'uri' => '/api/users',
                                'shortUri' => 'users',
                                'methods' => ['POST'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => '<b>[no error message]</b><br />
<small>'.__FILE__.'::215</small>
<p class="text-xs">[trace]</p>',
                                'metadata' => [],
                                'keywords' => ['/api/users', '/users'],
                            ],
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];

        yield 'Root level endpoints' => [
            'items' => [
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: '',
                        value: '/',
                    ),
                    methods: ['GET'],
                    schema: Schema::empty(),
                    keywords: ['/'],
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: '/',
                        value: '/_shouldnt_be_first',
                    ),
                    methods: ['GET'],
                    schema: Schema::empty(),
                    keywords: ['::fake::'],
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'users',
                        value: '/users',
                    ),
                    methods: ['GET'],
                    schema: Schema::empty(),
                    keywords: ['/users'],
                ),
            ],
            'expected' => [
                'v1' => [
                    [
                        'name' => '<root>',
                        'path' => '<root>',
                        'routes' => [
                            [
                                'uri' => '/',
                                'shortUri' => '',
                                'methods' => ['GET'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['/'],
                            ],
                        ],
                        'children' => [],
                    ],
                    [
                        'name' => '/',
                        'path' => '/',
                        'routes' => [ // <- This is just to test the order is preferring <root> over anything else.
                            [
                                'uri' => '/_shouldnt_be_first',
                                'shortUri' => '_shouldnt_be_first',
                                'methods' => ['GET'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['::fake::'],
                            ],
                        ],
                        'children' => [],
                    ],
                    [
                        'name' => 'users',
                        'path' => 'users',
                        'routes' => [
                            [
                                'uri' => '/users',
                                'shortUri' => 'users',
                                'methods' => ['GET'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => ['/users'],
                            ],
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];

        yield 'Nested endpoints' => [
            'items' => [
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'creator',
                        value: '/api/creator/auth/password/reset',
                    ),
                    methods: ['POST'],
                    schema: Schema::empty(),
                    keywords: [],
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'creator',
                        value: '/api/creator/profile',
                    ),
                    methods: ['GET'],
                    schema: Schema::empty(),
                    keywords: [],
                ),
            ],
            'expected' => [
                'v1' => [
                    [
                        'name' => 'creator',
                        'path' => 'creator',
                        'routes' => [
                            [
                                'uri' => '/api/creator/profile',
                                'shortUri' => 'creator/profile',
                                'methods' => ['GET'],
                                'schema' => [
                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                    'type' => 'object',
                                    'properties' => [],
                                    'required' => [],
                                    'additionalProperties' => false,
                                ],
                                'extractionError' => null,
                                'metadata' => [],
                                'keywords' => [],
                            ],
                        ],
                        'children' => [
                            [
                                'name' => 'auth',
                                'path' => 'creator/auth',
                                'routes' => [],
                                'children' => [
                                    [
                                        'name' => 'password',
                                        'path' => 'creator/auth/password',
                                        'routes' => [
                                            [
                                                'uri' => '/api/creator/auth/password/reset',
                                                'shortUri' => 'creator/auth/password/reset',
                                                'methods' => ['POST'],
                                                'schema' => [
                                                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                                                    'type' => 'object',
                                                    'properties' => [],
                                                    'required' => [],
                                                    'additionalProperties' => false,
                                                ],
                                                'extractionError' => null,
                                                'metadata' => [],
                                                'keywords' => [],
                                            ],
                                        ],
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /*
     * Helpers.
     */

    private function replaceTraceWithPlaceholder(array $output): array
    {
        return Arr::map(
            $output,
            fn (array $routesInVersion) => Arr::map(
                $routesInVersion,
                fn (array $folder) => $this->replaceTraceInFolder($folder),
            )
        );
    }

    private function replaceTraceInFolder(array $folder): array
    {
        $folder['routes'] = Arr::map($folder['routes'], function (array $route) {
            if ($route['extractionError'] === null) {
                return $route;
            }

            $route['extractionError'] = preg_replace('#(<p\b[^>]*>).*?(</p>)#si', '$1[trace]$2', $route['extractionError']);

            return $route;
        });

        $folder['children'] = Arr::map($folder['children'], fn (array $child) => $this->replaceTraceInFolder($child));

        return $folder;
    }
}
