<?php

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
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
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

        $output = $collection->toFrontendArray();

        // Assert

        $output = $this->replaceTraceWithPlaceholder($output);

        $this->assertSame(
            $expected,
            $output,
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
                    'posts' => [
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
                    'users' => [
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
                    'users' => [
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
<small>'.__FILE__.'::171</small>
<p class="text-xs">[trace]</p>',
                            'metadata' => [],
                            'keywords' => ['/api/users', '/users'],
                        ],
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
                    '<root>' => [
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
                    '/' => [ // <- This is just to test the order is preferring <root> over anything else.
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
                    'users' => [
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
                fn (array $resourceRoutes) => Arr::map(
                    $resourceRoutes,
                    function (array $route) {
                        if ($route['extractionError'] === null) {
                            return $route;
                        }

                        $route['extractionError'] = preg_replace('#(<p\b[^>]*>).*?(</p>)#si', '$1[trace]$2', $route['extractionError']);

                        return $route;
                    },
                )
            )
        );
    }
}
