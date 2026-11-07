<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Illuminate\Cache\RateLimiter;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Sunchayn\Nimbus\Modules\Magic\Services\ResettingThrottleRequests;

class ResetRateLimiterListener
{
    public const HEADER = 'X-Nimbus-Reset-Limiter';

    public function handle(RouteMatched $routeMatched): void
    {
        if ($routeMatched->request->header(self::HEADER) !== '1') {
            return;
        }

        // The route middleware is resolved after this event, so it receives the resetting version.
        app()->bind(
            ThrottleRequests::class,
            fn ($app): ResettingThrottleRequests => new ResettingThrottleRequests($app->make(RateLimiter::class)),
        );
    }
}
