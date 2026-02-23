<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractApplicationRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionInternalException;
use Sunchayn\Nimbus\Modules\Routes\Extractor\SchemaExtractor;
use Sunchayn\Nimbus\Modules\Routes\Factories\ExtractableRouteFactory;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExtractApplicationRoutesAction::class)]
#[CoversClass(RouteExtractionInternalException::class)]
class RouteExtractorServiceFunctionalTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router
            ->post('/api/users', fn () => response()->json(['users' => []]))
            ->name('api.users.store');

        $router
            ->get('/api/users/{id}', fn () => response()->json(['user' => []]))
            ->name('api.users.show');

        $router
            ->post('/api/posts', fn () => response()->json(['posts' => []]))
            ->name('api.posts.store');

        $router
            ->post('/non-api/posts', fn () => response()->json(['posts' => []]))
            ->name('non-api.posts.store');
    }

    public function test_it_processes_routes_properly(): void
    {
        // Anticipate

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (MockInterface $mock) {
            $mock->shouldReceive('getRoutesPrefix')->andReturn(['api']);
            $mock->shouldReceive('isVersioned')->andReturn($this->isVersioned = fake()->boolean());
            $mock->shouldReceive('getIncludedPrefixes')->andReturn([]);
            $mock->shouldReceive('getExcludedPrefixes')->andReturn([]);
        });

        $routeFactoryMock = $this->mock(ExtractableRouteFactory::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('fromLaravelRoute')
                ->withAnyArgs()
                ->andReturnUsing(fn (Route $route) => new ExtractableRoute(
                    parameters: ['test_placeholder:name' => $route->getName()],
                    codeParser: static fn () => '::fake::',
                ))
                ->times(3);
        });

        $schemaExtractorMock = $this->mock(SchemaExtractor::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('extract')
                ->withAnyArgs()
                ->andReturnUsing(
                    fn (ExtractableRoute $route) => new Schema([new StringSchemaProperty('foobar')]),
                )
                ->times(3);
        });

        // Arrange

        $routeExtractorService = resolve(ExtractApplicationRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Act

        $result = $routeExtractorService->execute($routes);

        // Assert

        $this->assertEquals(3, $result->count());

        $this->assertContainsOnlyInstancesOf(
            ExtractedRoute::class,
            $result,
        );

        $result->each(function (ExtractedRoute $extractedRoute) use ($routeFactoryMock, $schemaExtractorMock, $routes) {
            $this->assertTrue(
                str_starts_with($extractedRoute->uri->value, '/api'),
                "Route should start with api prefix: {$extractedRoute->uri->value}.",
            );

            $originalRoute = Arr::first(
                $routes,
                // We can do this simple check because we didn't set up routes that share the same URI.
                fn (Route $route) => '/'.$route->uri() === $extractedRoute->uri->value,
            );

            $this->assertEquals(
                array_values(
                    Arr::where(
                        $originalRoute->methods(),
                        fn (string $method) => ! in_array($method, ['HEAD']),
                    ),
                ),
                $extractedRoute->methods,
            );

            $this->assertEquals(
                [
                    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                    'type' => 'object',
                    'properties' => [
                        'foobar' => [
                            'type' => 'string',
                        ],
                    ],
                    'required' => [],
                    'additionalProperties' => false,
                ],
                $extractedRoute->schema->toArray(),
            );

            $this->assertNotNull($originalRoute);

            $this->assertEqualsCanonicalizing(
                array_filter([
                    $extractedRoute->uri->value,
                    $extractedRoute->uri->getShortUri(),
                    $originalRoute->getName(),
                ]),
                $extractedRoute->keywords,
            );

            $routeFactoryMock
                ->shouldHaveReceived(
                    'fromLaravelRoute',
                    fn (Route $routeArg) => $routeArg->uri() === $originalRoute->uri && $routeArg->methods() === $originalRoute->methods,
                );

            $schemaExtractorMock
                ->shouldHaveReceived(
                    'extract',
                    fn (ExtractableRoute $extractableRouteArg) => $extractableRouteArg->parameters['test_placeholder:name'] === $originalRoute->getName()
                        && ($extractableRouteArg->codeParser)() === '::fake::',
                );
        });
    }

    public function test_process_excludes_ignored_routes(): void
    {
        // Arrange

        $ignoredRoutesServiceMock = $this->mock(IgnoredRoutesService::class)->makePartial();

        $routeExtractorService = resolve(ExtractApplicationRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Anticipate

        $ignoredRoutesServiceMock->shouldReceive('hasIgnoredRoutes')->andReturnTrue();

        $ignoredRoutesServiceMock
            ->shouldReceive('isIgnored')
            ->withArgs(fn (Route $route) => $route->uri() === 'api/users' && $route->methods() === ['POST'])
            ->andReturnTrue();

        // Act

        $result = $routeExtractorService->execute($routes);

        // Assert

        $this->assertCount(2, $result);

        $ignoredRouteWithinResult = $result->first(
            fn (ExtractedRoute $extractedRoute) => $extractedRoute->uri->value === '/api/users'
                && in_array('POST', $extractedRoute->methods),
        );

        $this->assertNull(
            $ignoredRouteWithinResult,
            'Ignored route should not be in results',
        );
    }

    public function test_process_handles_empty_routes_array(): void
    {
        // Arrange

        $routeExtractorService = resolve(ExtractApplicationRoutesAction::class);

        $routes = [];

        // Act

        $result = $routeExtractorService->execute($routes);

        // Assert

        $this->assertEquals(0, $result->count());
    }

    public function test_process_uses_config_prefix(): void
    {
        // Arrange

        RouteFacade::post('/custom/test', fn () => response()->json(['test' => true]))
            ->name('custom.test');

        $activeApplicationResolverMock = $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class);

        $routeExtractorService = resolve(ExtractApplicationRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Anticipate

        $activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn(['custom']);
        $activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(false);
        $activeApplicationResolverMock->shouldReceive('getIncludedPrefixes')->andReturn([]);
        $activeApplicationResolverMock->shouldReceive('getExcludedPrefixes')->andReturn([]);

        // Act

        $result = $routeExtractorService->execute($routes);

        // Assert

        $customRouteWithinResult = $result->first(
            fn (ExtractedRoute $extractedRoute) => str_starts_with($extractedRoute->uri->value, '/custom'),
        );

        $this->assertNotNull(
            $customRouteWithinResult,
            'Should include routes with custom prefix',
        );
    }

    public function test_it_returns_routes_with_error_schema_when_extraction_fails(): void
    {
        // Anticipate

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (MockInterface $mock) {
            $mock->shouldReceive('getRoutesPrefix')->andReturn(['api']);
            $mock->shouldReceive('isVersioned')->andReturn(false);
            $mock->shouldReceive('getIncludedPrefixes')->andReturn([]);
            $mock->shouldReceive('getExcludedPrefixes')->andReturn([]);
        });

        $this->mock(SchemaExtractor::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('extract')
                ->withAnyArgs()
                ->andThrow(new RuntimeException('Schema extraction failed'));
        });

        // Arrange

        $routeExtractorService = resolve(ExtractApplicationRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Act — no exception should be thrown; routes are returned with error schemas

        $result = $routeExtractorService->execute($routes);

        // Assert — routes are returned, each with an extractionError on the schema

        $this->assertGreaterThan(0, $result->count());

        foreach ($result as $extractedRoute) {
            $this->assertNotNull($extractedRoute->schema->extractionError);
        }
    }

    public function test_it_skips_routes_when_factory_fails(): void
    {
        // Anticipate

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (MockInterface $mock) {
            $mock->shouldReceive('getRoutesPrefix')->andReturn(['api']);
            $mock->shouldReceive('isVersioned')->andReturn(false);
            $mock->shouldReceive('getIncludedPrefixes')->andReturn([]);
            $mock->shouldReceive('getExcludedPrefixes')->andReturn([]);
        });

        $this->mock(ExtractableRouteFactory::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive('fromLaravelRoute')
                ->withAnyArgs()
                ->andThrow(new RuntimeException('Controller method not found'));
        });

        // Arrange

        $routeExtractorService = resolve(ExtractApplicationRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Act — no exception thrown; routes are skipped gracefully

        $result = $routeExtractorService->execute($routes);

        // Assert — all api routes skipped, none returned

        $this->assertCount(0, $result);
        $this->assertTrue($result->hasSkippedRoutes());

        $skippedRoutes = $result->getSkippedRoutes();

        $this->assertCount(3, $skippedRoutes);

        foreach ($skippedRoutes as $skippedRoute) {
            $this->assertStringStartsWith('api/', $skippedRoute['uri']);
            $this->assertNotContains('HEAD', $skippedRoute['methods']);

            // RouteExtractionInternalException wraps the original message
            $this->assertStringContainsString(
                'Controller method not found',
                $skippedRoute['reason'],
            );

            $this->assertStringContainsString(
                'due to an unexpected error',
                $skippedRoute['reason'],
            );
        }
    }
}
