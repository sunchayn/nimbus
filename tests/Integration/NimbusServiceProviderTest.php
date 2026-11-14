<?php

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Events\RouteMatched;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\OpenAPISchemaRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\RoutesProcessorContract;
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
    }

    public function test_it_does_not_register_services_when_disabled(): void
    {
        // Arrange

        $app = new Application(__DIR__);
        $app['env'] = 'production';
        $app['config'] = new Repository([
            'nimbus' => ['allowed_envs' => ['local']],
        ]);

        $provider = new NimbusServiceProvider($app);

        // Act

        $provider->register();
        $provider->boot();

        // Assert

        $this->assertFalse($app->bound(IgnoredRoutesService::class));
        $this->assertFalse($app->bound(RoutesProcessorContract::class));
        $this->assertFalse($app['events']->hasListeners(RouteMatched::class));
    }

    public function test_it_listens_for_matched_routes_when_enabled(): void
    {
        // Act & Assert

        $this->assertTrue($this->app['events']->hasListeners(RouteMatched::class));
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
}
