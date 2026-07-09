<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Route;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Collections\Contracts\CollectionStoreContract;
use Sunchayn\Nimbus\Modules\Collections\Stores\JsonFileCollectionStore;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\OpenAPISchemaRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\RoutesProcessorContract;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\NimbusServiceProvider;

#[CoversClass(NimbusServiceProvider::class)]
class NimbusServiceProviderTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [NimbusServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nimbus.allowed_envs', ['testing']);
    }

    public function test_it_registers_package_when_enabled(): void
    {
        // Assert

        $this->assertTrue($this->app->bound(IgnoredRoutesService::class));
        $this->assertTrue($this->app->bound(RoutesProcessorContract::class));
        $this->assertTrue($this->app->bound(CollectionStoreContract::class));
    }

    public function test_it_binds_the_json_file_collection_store_by_default(): void
    {
        // Act

        $store = $this->app->make(CollectionStoreContract::class);

        // Assert

        $this->assertInstanceOf(JsonFileCollectionStore::class, $store);
    }

    public function test_it_does_not_register_services_when_disabled(): void
    {
        // Arrange

        $app = new Application(__DIR__);
        $app['env'] = 'production';
        $app['config'] = new \Illuminate\Config\Repository([
            'nimbus' => ['allowed_envs' => ['local']],
        ]);

        $provider = new NimbusServiceProvider($app);

        // Act

        $provider->register();
        $provider->boot();

        // Assert

        $this->assertFalse($app->bound(IgnoredRoutesService::class));
        $this->assertFalse($app->bound(RoutesProcessorContract::class));
    }

    public function test_it_binds_auto_detect_routes_processor_by_default(): void
    {
        // Arrange

        $this->app['config']->set('nimbus.routes.processing_strategy', 'auto_detect');

        // Act

        $processor = $this->app->make(RoutesProcessorContract::class);

        // Assert

        $this->assertInstanceOf(AutoDetectRoutesProcessor::class, $processor);
    }

    public function test_it_binds_openapi_routes_processor_when_configured(): void
    {
        // Arrange

        // We need to mock ActiveApplicationResolver because it's used in the binding closure
        $mockResolver = Mockery::mock(ActiveApplicationResolver::class);
        $mockResolver->shouldReceive('getRouteExtractionStrategy')
            ->andReturn(RoutesProcessingStrategyEnum::OpenAPI);

        $this->app->instance(ActiveApplicationResolver::class, $mockResolver);

        // Act

        $processor = $this->app->make(RoutesProcessorContract::class);

        // Assert

        $this->assertInstanceOf(OpenAPISchemaRoutesProcessor::class, $processor);
    }

    public function test_it_rolls_back_transaction_on_termination(): void
    {
        // Arrange

        $dbMock = Mockery::mock(DatabaseManager::class);

        $dbMock->shouldReceive('rollBack')->once();

        $this->app->instance('db', $dbMock);

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Nimbus-Transaction-Mode', '1');

        $route = new Route(['GET'], '/test', fn () => 'ok');
        $event = new RouteMatched($route, $request);

        // Anticipate

        $dbMock->shouldReceive('beginTransaction'); // Triggered by event
        $dbMock->shouldReceive('transactionLevel')->andReturn(1);

        // Act

        $this->app['events']->dispatch($event);

        // Trigger termination logic manually
        $this->app->terminate();
    }

    public function test_it_ignores_transaction_mode_if_header_missing(): void
    {
        // Arrange

        $dbMock = Mockery::mock(DatabaseManager::class);
        $dbMock->shouldNotReceive('beginTransaction');

        $this->app->instance('db', $dbMock);

        $request = Request::create('/test', 'GET');

        // No header

        $route = new Route(['GET'], '/test', fn () => 'ok');
        $event = new RouteMatched($route, $request);

        // Act

        $this->app['events']->dispatch($event);
    }
}
