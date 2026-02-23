<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use Illuminate\Routing\Route;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractApplicationRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Extractor\SchemaExtractor;
use Sunchayn\Nimbus\Modules\Routes\Factories\ExtractableRouteFactory;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions\Stubs\AppControllerStub;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExtractApplicationRoutesAction::class)]
class ExtractApplicationRoutesActionUnitTest extends TestCase
{
    private MockInterface&SchemaExtractor $schemaExtractorMock;

    private MockInterface&ExtractableRouteFactory $routeFactoryMock;

    private MockInterface&IgnoredRoutesService $ignoredRoutesServiceMock;

    private MockInterface&ActiveApplicationResolver $resolverMock;

    private MockInterface&LoggerInterface $loggerMock;

    /**
     * Point base_path() to the package root so that isAppRoute()
     * can correctly distinguish vendor/ classes from app classes.
     */
    protected function getBasePath(): string
    {
        return realpath(dirname(__DIR__, 5));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure base_path('vendor') resolves to the actual package vendor directory.
        $this->app->setBasePath(realpath(dirname(__DIR__, 5)));

        $this->schemaExtractorMock = Mockery::mock(SchemaExtractor::class);
        $this->routeFactoryMock = Mockery::mock(ExtractableRouteFactory::class);
        $this->ignoredRoutesServiceMock = Mockery::mock(IgnoredRoutesService::class);
        $this->resolverMock = Mockery::mock(ActiveApplicationResolver::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }

    public function test_it_filters_routes_by_prefix(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api']);

        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $webRoute = $this->createAppRouteMock('dashboard', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$apiRoute, $webRoute]);

        // Assert

        $this->assertCount(1, $result);
    }

    public function test_it_filters_routes_by_prefix_with_leading_slash(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api']);

        $route = $this->createAppRouteMock('/api/users', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($route);

        // Act

        $result = $this->createAction()->execute([$route]);

        // Assert

        $this->assertCount(1, $result);
    }

    public function test_it_excludes_vendor_routes_by_default(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $appRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $vendorRoute = $this->createVendorRouteMock('telescope/requests', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($appRoute);

        // Act

        $result = $this->createAction()->execute([$appRoute, $vendorRoute]);

        // Assert

        $this->assertCount(1, $result);
    }

    public function test_included_prefixes_opts_in_vendor_routes(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api'], includedPrefixes: ['telescope']);

        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $telescopeRoute = $this->createVendorRouteMock('telescope/requests', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);
        $this->stubSchemaExtraction($telescopeRoute);

        // Act

        $result = $this->createAction()->execute([$apiRoute, $telescopeRoute]);

        // Assert

        $this->assertCount(2, $result);
    }

    public function test_included_prefixes_bypasses_prefix_filter(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api'], includedPrefixes: ['telescope']);

        $telescopeRoute = $this->createVendorRouteMock('telescope/requests', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($telescopeRoute);

        // Act

        $result = $this->createAction()->execute([$telescopeRoute]);

        // Assert — telescope doesn't start with 'api' but is included via included_prefixes

        $this->assertCount(1, $result);
    }

    public function test_excluded_prefixes_rejects_app_routes(): void
    {
        // Arrange

        $this->configureResolver(prefix: [], excludedPrefixes: ['internal']);

        $publicRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $internalRoute = $this->createAppRouteMock('internal/health', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($publicRoute);

        // Act

        $result = $this->createAction()->execute([$publicRoute, $internalRoute]);

        // Assert

        $this->assertCount(1, $result);
    }

    public function test_it_normalises_uris_without_leading_slash(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api']);

        $route = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($route);

        // Act

        $result = $this->createAction()->execute([$route]);

        // Assert — URI should be normalised with a leading slash

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_it_handles_root_routes(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $rootRoute = $this->createAppRouteMock('/', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($rootRoute);

        // Act

        $result = $this->createAction()->execute([$rootRoute]);

        // Assert — root route IS returned with resource '/'

        $this->assertCount(1, $result);
        $this->assertEquals('/', $result->first()->uri->resource);
    }

    public function test_it_returns_routes_with_error_schema_when_extraction_fails(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $validRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $failingRoute = $this->createAppRouteMock('broken/endpoint', ['POST']);

        $this->stubSchemaExtraction($validRoute);

        $failingExtractableRoute = ExtractableRoute::empty();

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($failingRoute)
            ->andReturn($failingExtractableRoute);

        $this->schemaExtractorMock->shouldReceive('extract')
            ->with($failingExtractableRoute)
            ->andThrow(new \RuntimeException('Schema extraction failed'));

        $this->loggerMock->shouldReceive('warning')->once();

        // Act

        $result = $this->createAction()->execute([$validRoute, $failingRoute]);

        // Assert — both routes returned, failing one has extractionError

        $this->assertCount(2, $result);
        $this->assertNull($result->first()->schema->extractionError);
        $this->assertNotNull($result->last()->schema->extractionError);
    }

    public function test_it_skips_routes_when_factory_fails(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $validRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $brokenRoute = $this->createAppRouteMock('broken/missing-method', ['POST']);

        $this->stubSchemaExtraction($validRoute);

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($brokenRoute)
            ->andThrow(new \RuntimeException('Controller method not found'));

        $this->loggerMock->shouldReceive('error')->once();
        $this->loggerMock->shouldReceive('warning')->once();

        // Act

        $result = $this->createAction()->execute([$validRoute, $brokenRoute]);

        // Assert — only valid route returned, broken one skipped

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_it_handles_root_routes_with_prefix(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api']);

        $rootRoute = $this->createAppRouteMock('api', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($rootRoute);

        // Act

        $result = $this->createAction()->execute([$rootRoute]);

        // Assert — prefix-root route IS returned with resource '/'

        $this->assertCount(1, $result);
        $this->assertEquals('/', $result->first()->uri->resource);
        $this->assertEquals('api', $result->first()->uri->prefix);
    }

    public function test_it_populates_skipped_routes_for_factory_failures(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $validRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $brokenRoute = $this->createAppRouteMock('broken/missing-method', ['POST']);

        $this->stubSchemaExtraction($validRoute);

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($brokenRoute)
            ->andThrow(new \RuntimeException('Controller method not found'));

        $this->loggerMock->shouldReceive('error')->once();
        $this->loggerMock->shouldReceive('warning')->once();

        // Act

        $result = $this->createAction()->execute([$validRoute, $brokenRoute]);

        // Assert — skippedRoutes is populated with the factory failure

        $this->assertTrue($result->hasSkippedRoutes());
        $this->assertCount(1, $result->getSkippedRoutes());
        $this->assertEquals('broken/missing-method', $result->getSkippedRoutes()[0]['uri']);
    }

    public function test_schema_extraction_failures_do_not_populate_skipped_routes(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $failingRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);

        $failingExtractableRoute = ExtractableRoute::empty();

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($failingRoute)
            ->andReturn($failingExtractableRoute);

        $this->schemaExtractorMock->shouldReceive('extract')
            ->with($failingExtractableRoute)
            ->andThrow(new \RuntimeException('Schema extraction failed'));

        $this->loggerMock->shouldReceive('warning')->once();

        // Act

        $result = $this->createAction()->execute([$failingRoute]);

        // Assert — route is included (not skipped), skippedRoutes is empty

        $this->assertCount(1, $result);
        $this->assertFalse($result->hasSkippedRoutes());
    }

    public function test_it_assigns_correct_prefix_with_multiple_prefixes(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['admin', 'api']);

        $adminRoute = $this->createAppRouteMock('admin/users', ['GET', 'HEAD']);
        $apiRoute = $this->createAppRouteMock('api/orders', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($adminRoute);
        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$adminRoute, $apiRoute]);

        // Assert

        $this->assertCount(2, $result);
        $this->assertEquals('admin', $result->first()->uri->prefix);
        $this->assertEquals('api', $result->last()->uri->prefix);
    }

    public function test_it_assigns_empty_prefix_when_no_prefixes_configured(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $route = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($route);

        // Act

        $result = $this->createAction()->execute([$route]);

        // Assert

        $this->assertCount(1, $result);
        $this->assertEquals('', $result->first()->uri->prefix);
    }

    public function test_excluded_prefixes_takes_priority_over_included_prefixes(): void
    {
        // Arrange

        $this->configureResolver(
            prefix: ['api'],
            includedPrefixes: ['telescope'],
            excludedPrefixes: ['telescope'],
        );

        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $telescopeRoute = $this->createVendorRouteMock('telescope/requests', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$apiRoute, $telescopeRoute]);

        // Assert — telescope is excluded even though also included

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_it_filters_by_multiple_prefixes(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['admin', 'api']);

        $adminRoute = $this->createAppRouteMock('admin/dashboard', ['GET', 'HEAD']);
        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $webRoute = $this->createAppRouteMock('web/home', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($adminRoute);
        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$adminRoute, $apiRoute, $webRoute]);

        // Assert — only admin and api routes, web is filtered out

        $this->assertCount(2, $result);
    }

    public function test_it_handles_both_factory_and_schema_failures_in_same_batch(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $validRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $factoryFailRoute = $this->createAppRouteMock('broken/factory', ['POST']);
        $schemaFailRoute = $this->createAppRouteMock('broken/schema', ['PUT']);

        $this->stubSchemaExtraction($validRoute);

        // Factory failure — route skipped entirely
        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($factoryFailRoute)
            ->andThrow(new \RuntimeException('Controller method not found'));

        // Schema failure — route returned with error badge
        $schemaFailExtractableRoute = ExtractableRoute::empty();

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($schemaFailRoute)
            ->andReturn($schemaFailExtractableRoute);

        $this->schemaExtractorMock->shouldReceive('extract')
            ->with($schemaFailExtractableRoute)
            ->andThrow(new \RuntimeException('Schema extraction failed'));

        $this->loggerMock->shouldReceive('error')->once();
        $this->loggerMock->shouldReceive('warning')->twice();

        // Act

        $result = $this->createAction()->execute([$validRoute, $factoryFailRoute, $schemaFailRoute]);

        // Assert — 2 routes returned (valid + schema-fail), 1 skipped (factory-fail)

        $this->assertCount(2, $result);
        $this->assertNull($result->first()->schema->extractionError);
        $this->assertNotNull($result->last()->schema->extractionError);

        $this->assertTrue($result->hasSkippedRoutes());
        $this->assertCount(1, $result->getSkippedRoutes());
        $this->assertEquals('broken/factory', $result->getSkippedRoutes()[0]['uri']);
    }

    public function test_root_route_is_filtered_out_when_prefix_configured(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api']);

        $rootRoute = $this->createAppRouteMock('/', ['GET', 'HEAD']);
        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$rootRoute, $apiRoute]);

        // Assert — root route '/' does not start with 'api', so it is filtered out

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_vendor_routes_not_matching_included_prefixes_are_rejected(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api'], includedPrefixes: ['horizon']);

        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $telescopeRoute = $this->createVendorRouteMock('telescope/requests', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$apiRoute, $telescopeRoute]);

        // Assert — telescope is a vendor route and not in included_prefixes, so rejected

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_it_excludes_routes_matching_multiple_excluded_prefixes(): void
    {
        // Arrange

        $this->configureResolver(prefix: [], excludedPrefixes: ['internal', 'debug']);

        $publicRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $internalRoute = $this->createAppRouteMock('internal/health', ['GET', 'HEAD']);
        $debugRoute = $this->createAppRouteMock('debug/logs', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($publicRoute);

        // Act

        $result = $this->createAction()->execute([$publicRoute, $internalRoute, $debugRoute]);

        // Assert — both internal and debug routes excluded

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_it_includes_routes_matching_multiple_included_prefixes(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api'], includedPrefixes: ['telescope', 'horizon']);

        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $telescopeRoute = $this->createVendorRouteMock('telescope/requests', ['GET', 'HEAD']);
        $horizonRoute = $this->createVendorRouteMock('horizon/dashboard', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);
        $this->stubSchemaExtraction($telescopeRoute);
        $this->stubSchemaExtraction($horizonRoute);

        // Act

        $result = $this->createAction()->execute([$apiRoute, $telescopeRoute, $horizonRoute]);

        // Assert — all three included

        $this->assertCount(3, $result);
    }

    public function test_it_strips_head_method_from_skipped_routes(): void
    {
        // Arrange

        $this->configureResolver(prefix: []);

        $brokenRoute = $this->createAppRouteMock('broken/route', ['GET', 'HEAD']);

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($brokenRoute)
            ->andThrow(new \RuntimeException('Controller method not found'));

        $this->loggerMock->shouldReceive('error')->once();
        $this->loggerMock->shouldReceive('warning')->once();

        // Act

        $result = $this->createAction()->execute([$brokenRoute]);

        // Assert — HEAD is stripped from the skipped routes methods

        $this->assertCount(1, $result->getSkippedRoutes());
        $this->assertEquals(['GET'], $result->getSkippedRoutes()[0]['methods']);
    }

    public function test_ignored_routes_are_excluded(): void
    {
        // Arrange — configure resolver without the configureResolver helper
        // so we can control ignoredRoutesService separately

        $this->resolverMock->shouldReceive('getRoutesPrefix')->andReturn([]);
        $this->resolverMock->shouldReceive('isVersioned')->andReturn(false);
        $this->resolverMock->shouldReceive('getIncludedPrefixes')->andReturn([]);
        $this->resolverMock->shouldReceive('getExcludedPrefixes')->andReturn([]);

        $this->ignoredRoutesServiceMock->shouldReceive('hasIgnoredRoutes')->andReturn(true);

        $includedRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);
        $ignoredRoute = $this->createAppRouteMock('api/posts', ['POST']);

        $this->ignoredRoutesServiceMock->shouldReceive('isIgnored')
            ->with($ignoredRoute)
            ->andReturn(true);

        $this->ignoredRoutesServiceMock->shouldReceive('isIgnored')
            ->with($includedRoute)
            ->andReturn(false);

        $this->stubSchemaExtraction($includedRoute);

        // Act

        $result = $this->createAction()->execute([$includedRoute, $ignoredRoute]);

        // Assert — ignored route is excluded

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    public function test_route_matching_both_prefix_and_included_prefix_is_included(): void
    {
        // Arrange

        $this->configureResolver(prefix: ['api'], includedPrefixes: ['api']);

        $apiRoute = $this->createAppRouteMock('api/users', ['GET', 'HEAD']);

        $this->stubSchemaExtraction($apiRoute);

        // Act

        $result = $this->createAction()->execute([$apiRoute]);

        // Assert — route passes via both prefix filter and included_prefixes early return

        $this->assertCount(1, $result);
        $this->assertEquals('/api/users', $result->first()->uri->value);
    }

    /*
     * Helpers.
     */

    private function createAction(): ExtractApplicationRoutesAction
    {
        return new ExtractApplicationRoutesAction(
            schemaExtractor: $this->schemaExtractorMock,
            routeFactory: $this->routeFactoryMock,
            ignoredRoutesService: $this->ignoredRoutesServiceMock,
            activeApplicationResolver: $this->resolverMock,
            logger: $this->loggerMock,
        );
    }

    private function configureResolver(
        array $prefix = [],
        bool $versioned = false,
        array $includedPrefixes = [],
        array $excludedPrefixes = [],
    ): void {
        $this->resolverMock->shouldReceive('getRoutesPrefix')->andReturn($prefix);
        $this->resolverMock->shouldReceive('isVersioned')->andReturn($versioned);
        $this->resolverMock->shouldReceive('getIncludedPrefixes')->andReturn($includedPrefixes);
        $this->resolverMock->shouldReceive('getExcludedPrefixes')->andReturn($excludedPrefixes);

        $this->ignoredRoutesServiceMock->shouldReceive('hasIgnoredRoutes')->andReturn(false);
    }

    /**
     * Creates a route mock that resolves to a controller class within the test
     * directory (outside vendor/), so isAppRoute() returns true.
     */
    private function createAppRouteMock(string $uri, array $methods): MockInterface&Route
    {
        $route = Mockery::mock(Route::class);

        $route->shouldReceive('uri')->andReturn($uri);
        $route->shouldReceive('methods')->andReturn($methods);
        $route->shouldReceive('getAction')->with('uses')->andReturn(AppControllerStub::class.'@index');
        $route->shouldReceive('getControllerClass')->andReturn(AppControllerStub::class);
        $route->shouldReceive('getName')->andReturn(null);

        return $route;
    }

    /**
     * Creates a route mock that resolves to a controller class within vendor/,
     * so isAppRoute() returns false.
     *
     * Uses PHPUnit's TestCase class since it lives inside vendor/ and
     * is guaranteed to be present in the test environment.
     */
    private function createVendorRouteMock(string $uri, array $methods): MockInterface&Route
    {
        $vendorClass = \PHPUnit\Framework\TestCase::class;

        $route = Mockery::mock(Route::class);

        $route->shouldReceive('uri')->andReturn($uri);
        $route->shouldReceive('methods')->andReturn($methods);
        $route->shouldReceive('getAction')->with('uses')->andReturn($vendorClass.'@setUp');
        $route->shouldReceive('getControllerClass')->andReturn($vendorClass);
        $route->shouldReceive('getName')->andReturn(null);

        return $route;
    }

    private function stubSchemaExtraction(MockInterface&Route $route): void
    {
        $extractableRoute = ExtractableRoute::empty();

        $this->routeFactoryMock->shouldReceive('fromLaravelRoute')
            ->with($route)
            ->andReturn($extractableRoute);

        $this->schemaExtractorMock->shouldReceive('extract')
            ->with($extractableRoute)
            ->andReturn(Schema::empty());
    }
}
