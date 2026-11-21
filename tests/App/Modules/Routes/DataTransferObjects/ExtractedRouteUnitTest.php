<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\DataTransferObjects;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

#[CoversClass(ExtractedRoute::class)]
class ExtractedRouteUnitTest extends TestCase
{
    public function test_it_generates_signatures_for_simple_uri(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        // Act

        $signatures = $route->getRouteSignatures();

        // Assert

        $this->assertEquals(['GET' => 'GET@api/users'], $signatures);
    }

    public function test_it_generates_signatures_for_multiple_methods(): void
    {
        // Arrange

        $route = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET', 'POST'],
            schema: Schema::empty(),
        );

        // Act

        $signatures = $route->getRouteSignatures();

        // Assert

        $this->assertEquals([
            'GET' => 'GET@api/users',
            'POST' => 'POST@api/users',
        ], $signatures);
    }

    #[DataProvider('parameterizedUriDataProvider')]
    public function test_it_normalizes_parameters_in_signatures(string $uri, string $expectedSignatureSuffix): void
    {
        // Arrange

        $route = new ExtractedRoute(
            uri: Endpoint::fromRaw($uri, 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        // Act

        $signatures = $route->getRouteSignatures();

        // Assert

        $this->assertEquals(['GET' => 'GET@'.$expectedSignatureSuffix], $signatures);
    }

    public static function parameterizedUriDataProvider(): Generator
    {
        yield 'single parameter' => [
            'uri' => 'api/users/{id}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'multiple parameters' => [
            'uri' => 'api/posts/{post}/comments/{comment}',
            'expectedSignatureSuffix' => 'api/posts/{}/comments/{}',
        ];

        yield 'parameter with underscore' => [
            'uri' => 'api/users/{user_id}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'leading slash is trimmed' => [
            'uri' => '/api/users/{id}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'trailing slash is trimmed' => [
            'uri' => 'api/users/{id}/',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];

        yield 'optional parameter treated as regular' => [
            'uri' => 'api/users/{id?}',
            'expectedSignatureSuffix' => 'api/users/{}',
        ];
    }

    public function test_it_splits_a_route_into_one_route_per_method_and_drops_the_implied_head(): void
    {
        // Arrange

        $route = $this->createRoute(
            uri: 'api/users',
            methods: [
                'GET',
                'HEAD',
                'POST',
            ],
        );

        // Act

        $splitRoutes = $route->splitByMethod();

        // Assert

        $splitRouteIds = array_map(
            static fn (ExtractedRoute $splitRoute): string => $splitRoute->id(),
            $splitRoutes,
        );

        $this->assertSame(
            [
                'GET api/users',
                'POST api/users',
            ],
            $splitRouteIds,
        );
    }

    public function test_it_keeps_head_when_it_is_the_only_method(): void
    {
        // Arrange

        $route = $this->createRoute(
            uri: 'api/ping',
            methods: ['HEAD'],
        );

        // Act

        $splitRoutes = $route->splitByMethod();

        // Assert

        $this->assertCount(
            expectedCount: 1,
            haystack: $splitRoutes,
        );

        $this->assertSame(
            'HEAD api/ping',
            $splitRoutes[0]->id(),
        );
    }

    #[DataProvider('references')]
    public function test_it_matches_a_reference(string $reference, bool $expected): void
    {
        // Arrange

        $route = $this->createRoute(
            uri: 'api/users',
            methods: ['GET'],
            metadata: ['name' => 'users.index'],
        );

        // Act

        $isMatching = $route->isReferencedBy(reference: $reference);

        // Assert

        $this->assertSame(
            $expected,
            $isMatching,
        );
    }

    public static function references(): Generator
    {
        yield 'the id' => [
            'reference' => 'get API/users',
            'expected' => true,
        ];

        yield 'the name' => [
            'reference' => 'users.index',
            'expected' => true,
        ];

        yield 'the uri' => [
            'reference' => '/api/users/',
            'expected' => true,
        ];

        yield 'another route' => [
            'reference' => 'POST api/users',
            'expected' => false,
        ];
    }

    public function test_it_reads_the_name_of_a_route(): void
    {
        // Arrange

        $route = $this->createRoute(
            uri: 'api/users',
            methods: ['GET'],
            metadata: ['name' => 'users.index'],
        );

        // Act

        $name = $route->name();

        // Assert

        $this->assertSame(
            'users.index',
            $name,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    #[DataProvider('unnamedRoutesMetadata')]
    public function test_it_has_no_name_without_a_usable_one(array $metadata): void
    {
        // Arrange

        $route = $this->createRoute(
            uri: 'api/users',
            methods: ['GET'],
            metadata: $metadata,
        );

        // Act

        $name = $route->name();

        // Assert

        $this->assertNull($name);
    }

    public static function unnamedRoutesMetadata(): Generator
    {
        yield 'no name' => [
            'metadata' => [],
        ];

        yield 'empty name' => [
            'metadata' => ['name' => ''],
        ];

        yield 'non string name' => [
            'metadata' => ['name' => 12],
        ];
    }

    public function test_it_uppercases_the_method(): void
    {
        // Arrange

        $route = $this->createRoute(
            uri: 'api/users',
            methods: ['post'],
        );

        // Act

        $method = $route->method();

        // Assert

        $this->assertSame(
            'POST',
            $method,
        );
    }

    /*
     * Generators.
     */

    /**
     * @param  array<int, string>  $methods
     * @param  array<string, mixed>  $metadata
     */
    private function createRoute(string $uri, array $methods, array $metadata = []): ExtractedRoute
    {
        $endpoint = Endpoint::fromRaw(
            uri: $uri,
            routesPrefix: 'api',
            isVersioned: false,
        );

        return new ExtractedRoute(
            uri: $endpoint,
            methods: $methods,
            schema: Schema::empty(),
            metadata: $metadata,
        );
    }
}
