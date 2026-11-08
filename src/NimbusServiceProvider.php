<?php

namespace Sunchayn\Nimbus;

use Illuminate\Container\Container;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Sunchayn\Nimbus\Console\Doctor\DoctorCommand;
use Sunchayn\Nimbus\Console\Snapshot\ClearRoutesSnapshotCommand;
use Sunchayn\Nimbus\Modules\Ast\Events\ClassFileResolved;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Magic\Listeners\ActorHeaderListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\InsightCollectorListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\ResetRateLimiterListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\SandboxModeListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\TimeTravelListener;
use Sunchayn\Nimbus\Modules\Magic\Listeners\TransactionModeListener;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\CacheCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\EventsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\ExceptionCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\GatesCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\HttpCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\JobsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\LogsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\MailCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\NotificationsCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\QueriesCollector;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightsService;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Support\InsightClock;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\AutoDetectRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\OpenAPISchemaRoutesProcessor;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies\RoutesProcessorContract;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;

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
            ->hasRoutes(['api', 'web'])
            ->hasCommand(ClearRoutesSnapshotCommand::class)
            ->hasCommand(DoctorCommand::class);
    }

    public function register(): void
    {
        if (! $this->enabled) {
            $this->registerPackageConfigs();

            return;
        }

        parent::register();

        $this->app->singleton(ManifestManager::class);
        $this->app->singleton(InsightsService::class);

        Event::listen(ClassFileResolved::class, [ManifestManager::class, 'trackFile']);

        $this->app->singleton(
            IgnoredRoutesService::class,
            fn (): IgnoredRoutesService => new IgnoredRoutesService,
        );

        $this->app->singleton(InsightClock::class);

        $this->app->tag(
            [
                QueriesCollector::class,
                LogsCollector::class,
                ExceptionCollector::class,
                GatesCollector::class,
                CacheCollector::class,
                JobsCollector::class,
                MailCollector::class,
                NotificationsCollector::class,
                HttpCollector::class,
                EventsCollector::class,
            ],
            InsightsService::COLLECTORS_TAG,
        );

        $this->app->when(InsightsService::class)->needs('$collectors')->giveTagged(InsightsService::COLLECTORS_TAG);

        $this->app->bind(RoutesProcessorContract::class, function (Container $container) {
            $routesProcessingStrategyEnum = $container->make(ActiveApplicationResolver::class)->getRouteExtractionStrategy();

            return match ($routesProcessingStrategyEnum) {
                RoutesProcessingStrategyEnum::OpenAPI => $container->make(OpenAPISchemaRoutesProcessor::class),
                default => $container->make(AutoDetectRoutesProcessor::class),
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

        Event::listen(RouteMatched::class, ActorHeaderListener::class);
        Event::listen(RouteMatched::class, TransactionModeListener::class);
        Event::listen(RouteMatched::class, TimeTravelListener::class);
        Event::listen(RouteMatched::class, ResetRateLimiterListener::class);
        Event::listen(RouteMatched::class, InsightCollectorListener::class);
        Event::listen(RouteMatched::class, SandboxModeListener::class);
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
