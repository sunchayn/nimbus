<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\Middleware\WorkbenchToken;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        // Testbench ignores a process APP_ENV and reads the env file only, so the E2E run is honored here.
        if (is_string($environment = getenv('APP_ENV'))) {
            $this->app->instance('env', $environment);
            $this->app['config']->set('app.env', $environment);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Testbench ignores the middleware callbacks of workbench/bootstrap/app.php, so the demo API group is set here.
        EncryptCookies::except(['appearance', 'sidebar_state']);

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('workbench.token', WorkbenchToken::class);
        $router->pushMiddlewareToGroup('api', AddQueuedCookiesToResponse::class);
        $router->pushMiddlewareToGroup('api', EncryptCookies::class);
    }
}
