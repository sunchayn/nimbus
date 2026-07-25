<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services\RoutesProcessors;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\RouteReconciliationService;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

#[CoversClass(RouteReconciliationService::class)]
class RouteReconciliationServiceUnitTest extends TestCase
{
    public function test_it_intersects_primary_and_secondary_routes(): void
    {
        // Arrange

        $processor = new RouteReconciliationService;

        $primaryRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $secondaryRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $onlyPrimaryRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/posts', 'api', false),
            methods: ['POST'],
            schema: Schema::empty(),
        );

        $onlySecondaryRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/comments', 'api', false),
            methods: ['PUT'],
            schema: Schema::empty(),
        );

        $primaryCollection = ExtractedRoutesCollection::make([$primaryRoute, $onlyPrimaryRoute]);
        $secondaryCollection = ExtractedRoutesCollection::make([$secondaryRoute, $onlySecondaryRoute]);

        // Act

        $result = $processor->execute($primaryCollection, $secondaryCollection);

        // Assert

        $this->assertCount(3, $result);

        // Check matched route (Primary + Secondary)
        $matched = $result->first(fn (ExtractedRoute $r) => $r->uri->value === 'api/users');
        $this->assertFalse($matched->metadata['isMissingImplementation']);
        $this->assertFalse($matched->metadata['isUndocumented']);

        // Check only Primary route
        $onlyPrimary = $result->first(fn (ExtractedRoute $r) => $r->uri->value === 'api/posts');
        $this->assertTrue($onlyPrimary->metadata['isMissingImplementation']);
        $this->assertFalse($onlyPrimary->metadata['isUndocumented']);

        // Check only Secondary route
        $onlySecondary = $result->first(fn (ExtractedRoute $r) => $r->uri->value === 'api/comments');
        $this->assertFalse($onlySecondary->metadata['isMissingImplementation']);
        $this->assertTrue($onlySecondary->metadata['isUndocumented']);
    }

    #[DataProvider('routeIntersectionDataProvider')]
    public function test_it_handles_parameterized_uris(string $uri1, string $uri2, bool $shouldMatch): void
    {
        // Arrange

        $processor = new RouteReconciliationService;

        $primaryRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw($uri1, 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $secondaryRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw($uri2, 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $primaryCollection = ExtractedRoutesCollection::make([$primaryRoute]);
        $secondaryCollection = ExtractedRoutesCollection::make([$secondaryRoute]);

        // Act

        $result = $processor->execute($primaryCollection, $secondaryCollection);

        // Assert

        $matched = $result->first(fn (ExtractedRoute $r) => $r->uri->value === $uri1);
        $this->assertEquals(! $shouldMatch, $matched->metadata['isMissingImplementation']);
    }

    public static function routeIntersectionDataProvider(): Generator
    {
        yield 'exact match' => [
            'uri1' => 'api/users/1',
            'uri2' => 'api/users/1',
            'shouldMatch' => true,
        ];

        yield 'parameterized match' => [
            'uri1' => 'api/users/{id}',
            'uri2' => 'api/users/{user}',
            'shouldMatch' => true,
        ];

        yield 'no match' => [
            'uri1' => 'api/users/{id}',
            'uri2' => 'api/posts/{id}',
            'shouldMatch' => false,
        ];

        yield 'leading slash match' => [
            'uri1' => '/api/users',
            'uri2' => 'api/users',
            'shouldMatch' => true,
        ];
    }

    public function test_it_handles_multiple_routes_with_same_method(): void
    {
        // Arrange

        $processor = new RouteReconciliationService;

        $externalRoutes = ExtractedRoutesCollection::make([
            new ExtractedRoute(
                uri: Endpoint::fromRaw('api/users', 'api', false),
                methods: ['GET'],
                schema: Schema::empty(),
            ),
            new ExtractedRoute(
                uri: Endpoint::fromRaw('api/products', 'api', false),
                methods: ['GET'],
                schema: Schema::empty(),
            ),
        ]);

        $appRoutes = ExtractedRoutesCollection::make([
            new ExtractedRoute(
                uri: Endpoint::fromRaw('api/users', 'api', false),
                methods: ['GET'],
                schema: Schema::empty(),
            ),
            new ExtractedRoute(
                uri: Endpoint::fromRaw('api/products', 'api', false),
                methods: ['GET'],
                schema: Schema::empty(),
            ),
        ]);

        // Act

        $result = $processor->execute($externalRoutes, $appRoutes);

        // Assert

        $this->assertCount(2, $result);

        $usersRoute = $result->first(fn (ExtractedRoute $r) => $r->uri->value === 'api/users');
        $productsRoute = $result->first(fn (ExtractedRoute $r) => $r->uri->value === 'api/products');

        $this->assertFalse($usersRoute->metadata['isMissingImplementation']);
        $this->assertFalse($usersRoute->metadata['isUndocumented']);

        $this->assertFalse($productsRoute->metadata['isMissingImplementation']);
        $this->assertFalse($productsRoute->metadata['isUndocumented']);
    }

    public function test_it_preserves_external_route_metadata(): void
    {
        // Arrange

        $processor = new RouteReconciliationService;

        $externalRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
            metadata: ['source' => 'openapi', 'deprecated' => true],
        );

        $appRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
        );

        $externalRoutes = ExtractedRoutesCollection::make([$externalRoute]);
        $appRoutes = ExtractedRoutesCollection::make([$appRoute]);

        // Act

        $result = $processor->execute($externalRoutes, $appRoutes);

        // Assert

        $this->assertCount(1, $result);
        $reconciledRoute = $result->first();

        $this->assertEquals('openapi', $reconciledRoute->metadata['source']);
        $this->assertTrue($reconciledRoute->metadata['deprecated']);
        $this->assertFalse($reconciledRoute->metadata['isMissingImplementation']);
    }

    public function test_it_merges_keywords_from_both_sources(): void
    {
        // Arrange

        $processor = new RouteReconciliationService;

        // OpenAPI route with operation ID keyword
        $externalRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
            metadata: ['operationId' => 'getAllUsers'],
            keywords: ['getAllUsers'], // Only operation ID
        );

        // Laravel route with endpoint, short URI, and route name keywords
        $appRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/users', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
            keywords: ['api/users', '/users', 'users.index'],
        );

        $externalRoutes = ExtractedRoutesCollection::make([$externalRoute]);
        $appRoutes = ExtractedRoutesCollection::make([$appRoute]);

        // Act

        $result = $processor->execute($externalRoutes, $appRoutes);

        // Assert

        $this->assertCount(1, $result);
        $reconciledRoute = $result->first();

        // Should have merged keywords: Laravel keywords first, then OpenAPI keywords
        $this->assertEquals(
            ['api/users', '/users', 'users.index', 'getAllUsers'],
            $reconciledRoute->keywords
        );
    }

    public function test_it_preserves_openapi_keywords_when_no_matching_application_route(): void
    {
        // Arrange

        $processor = new RouteReconciliationService;

        // OpenAPI route with no matching Laravel route
        $externalRoute = new ExtractedRoute(
            uri: Endpoint::fromRaw('api/products', 'api', false),
            methods: ['GET'],
            schema: Schema::empty(),
            metadata: ['operationId' => 'getAllProducts'],
            keywords: ['getAllProducts'],
        );

        $externalRoutes = ExtractedRoutesCollection::make([$externalRoute]);
        $appRoutes = ExtractedRoutesCollection::make([]);

        // Act

        $result = $processor->execute($externalRoutes, $appRoutes);

        // Assert

        $this->assertCount(1, $result);
        $reconciledRoute = $result->first();

        // Should only have OpenAPI keywords (no Laravel route to merge with)
        $this->assertEquals(['getAllProducts'], $reconciledRoute->keywords);
        $this->assertTrue($reconciledRoute->metadata['isMissingImplementation']);
    }
}
