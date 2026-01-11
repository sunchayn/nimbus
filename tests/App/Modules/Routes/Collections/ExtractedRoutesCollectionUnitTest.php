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

        $this->assertEquals(
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
                ),
                new ExtractedRoute(
                    uri: new Endpoint(
                        version: 'v1',
                        resource: 'users',
                        value: '/api/users',
                    ),
                    methods: ['POST'],
                    schema: Schema::empty(),
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
                        ],
                    ],
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
<small>'.__FILE__.'::161</small>
<p class="text-xs">[trace]</p>',
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
