<?php

namespace Sunchayn\Nimbus;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
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
use Sunchayn\Nimbus\Modules\Workspace\Services\WorkspaceLockFile;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\WorkspaceConfig;

class NimbusServiceProvider extends ServiceProvider
{
    private const NAME = 'nimbus';

    private bool $enabled;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->enabled = $this->app->environment(config('nimbus.allowed_envs', ['testing', 'local']));
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/nimbus.php'), self::NAME);

        if (! $this->enabled) {
            return;
        }

        $this->app->singleton(ManifestManager::class);
        $this->app->singleton(InsightsService::class);

        $this->app->bind(
            WorkspaceConfig::class,
            fn (Container $container): WorkspaceConfig => WorkspaceConfig::fromConfig($container->make(Repository::class)),
        );

        $this->app->singleton(WorkspaceLockFile::class);

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

        $this->loadRoutesFrom($this->packagePath('routes/api.php'));
        $this->loadRoutesFrom($this->packagePath('routes/web.php'));
        $this->loadViewsFrom($this->packagePath('resources/views'), self::NAME);

        $this->commands([
            ClearRoutesSnapshotCommand::class,
            DoctorCommand::class,
        ]);

        $this->registerPublishing();

        Event::listen(RouteMatched::class, ActorHeaderListener::class);
        Event::listen(RouteMatched::class, TransactionModeListener::class);
        Event::listen(RouteMatched::class, TimeTravelListener::class);
        Event::listen(RouteMatched::class, ResetRateLimiterListener::class);
        Event::listen(RouteMatched::class, InsightCollectorListener::class);
        Event::listen(RouteMatched::class, SandboxModeListener::class);
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $assets = $this->packagePath('resources/dist');

        $this->publishes([$assets => public_path('vendor/'.self::NAME)], self::NAME.'-assets');

        // Laravel assets are published after an update by default.
        // Nimbus always wants fresh assets, so they share that tag too.
        $this->publishes([$assets => public_path('vendor/'.self::NAME)], 'laravel-assets');

        $this->publishes(
            [$this->packagePath('config/nimbus.php') => config_path('nimbus.php')],
            self::NAME.'-config',
        );

        $this->publishes(
            [$this->packagePath('resources/views') => resource_path('views/vendor/'.self::NAME)],
            self::NAME.'-views',
        );
    }

    private function packagePath(string $path): string
    {
        return __DIR__.'/../'.$path;
    }
}
