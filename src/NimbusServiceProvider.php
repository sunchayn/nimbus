<?php

namespace Sunchayn\Nimbus;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Events\RouteMatched;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Sunchayn\Nimbus\Modules\Collections\Contracts\CollectionStoreContract;
use Sunchayn\Nimbus\Modules\Collections\Stores\JsonFileCollectionStore;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\OpenAPISchemaRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies\RoutesProcessorContract;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;

class NimbusServiceProvider extends PackageServiceProvider
{
    private bool $enabled;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->enabled = $this->app->environment(config('nimbus.allowed_envs', ['testing', 'local', 'staging']));
    }

    /**
     * @see https://github.com/spatie/laravel-package-tools
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('nimbus')
            ->hasConfigFile()
            ->hasViews('nimbus')
            ->hasAssets()
            ->hasRoutes(['api', 'web']);
    }

    public function register(): void
    {
        if (! $this->enabled) {
            $this->registerPackageConfigs();

            return;
        }

        parent::register();

        $this->app->singleton(
            IgnoredRoutesService::class,
            fn (): IgnoredRoutesService => new IgnoredRoutesService,
        );

        $this->app->bind(RoutesProcessorContract::class, function (Container $container) {
            $routesProcessingStrategyEnum = $container->make(ActiveApplicationResolver::class)->getRouteExtractionStrategy();

            return match ($routesProcessingStrategyEnum) {
                RoutesProcessingStrategyEnum::OpenAPI => $container->make(OpenAPISchemaRoutesProcessor::class),
                default => $container->make(AutoDetectRoutesProcessor::class),
            };
        });

        $this->app->bind(CollectionStoreContract::class, function (Container $container): CollectionStoreContract {
            $driver = config('nimbus.collections.driver', 'json');

            return match ($driver) {
                default => new JsonFileCollectionStore(
                    $container->make(Filesystem::class),
                    config('nimbus.collections.path'),
                ),
            };
        });
    }

    public function boot(): void
    {
        if (! $this->enabled) {
            return;
        }

        parent::boot();

        $this->tagAlongsideLaravelAssets();

        // Handle transaction mode for requests
        $this->app->get('events')->listen(RouteMatched::class, function (RouteMatched $routeMatched): void {
            if ($routeMatched->request->header('X-Nimbus-Transaction-Mode') === '1') {
                app('db')->beginTransaction();

                app()->terminating(function (): void {
                    if (app('db')->transactionLevel() > 0) {
                        app('db')->rollBack();
                    }
                });
            }
        });
    }

    /**
     * Laravel assets are configured by default in Laravel to be published post update.
     * We always want to force-publish the assets with Nimbus, so let's tag alongside them.
     */
    private function tagAlongsideLaravelAssets(): void
    {
        $vendorAssets = $this->package->basePath('/../resources/dist');
        $appAssets = public_path('vendor/'.$this->package->shortName());

        $this->publishes([$vendorAssets => $appAssets], 'laravel-assets');
    }
}
