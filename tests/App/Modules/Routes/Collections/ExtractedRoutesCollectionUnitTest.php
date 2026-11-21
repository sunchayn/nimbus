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
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
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
<small>'.__FILE__.'::216</small>
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

    public function test_it_finds_a_route_by_its_id_its_name_or_its_uri(): void
    {
        // Arrange

        $collection = $this->usersCollection();

        // Act

        $byId = $collection->findRouteOrFail(reference: 'post API/users');

        $byName = $collection->findRouteOrFail(reference: 'users.index');

        // Assert

        $this->assertSame(
            'POST api/users',
            $byId->id(),
        );

        $this->assertSame(
            'GET api/users',
            $byName->id(),
        );
    }

    public function test_it_suggests_the_matching_routes_of_an_ambiguous_uri(): void
    {
        // Arrange

        $collection = $this->usersCollection();

        // Act

        $exception = $this->catchNotFound(
            collection: $collection,
            reference: 'api/users',
        );

        // Assert

        $this->assertSame(
            ['POST api/users', 'GET api/users'],
            $exception->suggestions,
        );
    }

    public function test_it_suggests_the_nearest_ids_for_an_unknown_route(): void
    {
        // Arrange

        $collection = $this->usersCollection();

        // Act

        $exception = $this->catchNotFound(
            collection: $collection,
            reference: 'POST api/user',
        );

        // Assert

        $this->assertContains(
            'POST api/users',
            $exception->suggestions,
        );
    }

    public function test_it_lists_one_route_per_method(): void
    {
        // Arrange

        $collection = $this->usersCollection();

        // Act

        $ids = $collection
            ->perMethod()
            ->map(static fn (ExtractedRoute $extractedRoute): string => $extractedRoute->id())
            ->all();

        // Assert

        $this->assertSame(
            ['POST api/users', 'GET api/users'],
            $ids,
        );
    }

    public function test_it_lists_the_routes_a_reference_matches(): void
    {
        // Arrange

        $collection = $this->usersCollection();

        // Act

        $byUri = $collection->matching(reference: ' /api/users/ ');

        $byName = $collection->matching(reference: 'users.index');

        $byNothing = $collection->matching(reference: 'api/posts');

        // Assert

        $this->assertCount(
            2,
            $byUri,
        );

        $this->assertSame(
            ['GET api/users'],
            $byName
                ->map(static fn (ExtractedRoute $extractedRoute): string => $extractedRoute->id())
                ->all(),
        );

        $this->assertTrue($byNothing->isEmpty());
    }

    /*
     * Generators.
     */

    private function usersCollection(): ExtractedRoutesCollection
    {
        $postUsersRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw(
                uri: 'api/users',
                routesPrefix: 'api',
                isVersioned: false,
            ),
            methods: ['POST'],
            schema: Schema::empty(),
        );

        $getUsersRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw(
                uri: 'api/users',
                routesPrefix: 'api',
                isVersioned: false,
            ),
            methods: ['GET', 'HEAD'],
            schema: Schema::empty(),
            metadata: ['name' => 'users.index'],
        );

        return new ExtractedRoutesCollection(
            items: [
                $postUsersRoute,
                $getUsersRoute,
            ],
        );
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

    private function catchNotFound(ExtractedRoutesCollection $collection, string $reference): RouteNotFoundException
    {
        try {
            $collection->findRouteOrFail(reference: $reference);
        } catch (RouteNotFoundException $routeNotFoundException) {
            return $routeNotFoundException;
        }

        $this->fail('The route was found.');
    }
}
