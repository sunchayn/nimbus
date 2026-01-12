<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionInternalException;
use Sunchayn\Nimbus\Modules\Routes\Extractor\SchemaExtractor;
use Sunchayn\Nimbus\Modules\Routes\Factories\ExtractableRouteFactory;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\ExtractableRoute;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExtractRoutesAction::class)]
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
            $mock->shouldReceive('getRoutesPrefix')->andReturn('api');
            $mock->shouldReceive('isVersioned')->andReturn($this->isVersioned = fake()->boolean());
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

        $routeExtractorService = resolve(ExtractRoutesAction::class);

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
                str_starts_with($extractedRoute->uri->value, 'api'),
                "Route should start with api prefix: {$extractedRoute->uri->value}.",
            );

            $originalRoute = Arr::first(
                $routes,
                // We can do this simple check because we didn't set up routes that share the same URI.
                fn (Route $route) => $route->uri() === $extractedRoute->uri->value,
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

        $routeExtractorService = resolve(ExtractRoutesAction::class);

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
            fn (ExtractedRoute $extractedRoute) => $extractedRoute->uri->value === 'api/users'
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

        $routeExtractorService = resolve(ExtractRoutesAction::class);

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

        $routeExtractorService = resolve(ExtractRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Anticipate

        $activeApplicationResolverMock->shouldReceive('getRoutesPrefix')->andReturn('custom');
        $activeApplicationResolverMock->shouldReceive('isVersioned')->andReturn(false);

        // Act

        $result = $routeExtractorService->execute($routes);

        // Assert

        $customRouteWithinResult = $result->first(
            fn (ExtractedRoute $extractedRoute) => str_starts_with($extractedRoute->uri->value, 'custom'),
        );

        $this->assertNotNull(
            $customRouteWithinResult,
            'Should include routes with custom prefix',
        );
    }

    public function test_it_handles_extraction_errors_gracefully(): void
    {
        // Anticipate

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (MockInterface $mock) {
            $mock->shouldReceive('getRoutesPrefix')->andReturn('api');
            $mock->shouldReceive('isVersioned')->andReturn(fake()->boolean());
        });

        $dummyFailingException = new RuntimeException(message: $dummyFailingExceptionMessage = fake()->sentence());

        $this->mock(SchemaExtractor::class, function (MockInterface $mock) use ($dummyFailingException) {
            $mock
                ->shouldReceive('extract')
                ->withAnyArgs()
                ->andThrow($dummyFailingException);
        });

        // Arrange

        $routeExtractorService = resolve(ExtractRoutesAction::class);

        $routes = RouteFacade::getRoutes()->getRoutes();

        // Act

        try {
            $result = $routeExtractorService->execute($routes);
        } catch (RouteExtractionInternalException $exception) {
        }

        // Assert

        $this->assertNotNull($exception);

        $this->assertSame(
            $dummyFailingException,
            $exception->getPrevious(),
        );

        $this->assertEquals(
            [
                'uri' => 'api/users', // <- First route from the list self::defineRoutes.
                'methods' => ['POST'],
                'controllerClass' => '[unspecified]',
                'controllerMethod' => '[unspecified]',
            ],
            $exception->getRouteContext()
        );

        $this->assertEquals(
            "Failed to extract route information for 'api/users' due to an unexpected error: {$dummyFailingExceptionMessage}",
            $exception->getMessage(),
        );

        $this->assertEquals(
            'Check the application logs for more details and ensure all dependencies are properly installed.'
            .'<br />In case of internal errors, please open an issue: <a class="hover:underline" href="https://github.com/sunchayn/nimbus/issues/new/choose">https://github.com/sunchayn/nimbus/issues/new/choose</a>',
            $exception->getSuggestedSolution()
        );
    }
}
