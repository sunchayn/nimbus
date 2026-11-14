<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Clears every limiter key of the request before the limits are checked.
 * The keys are the ones Laravel built for the route and the actor, so they always match.
 */
class ResettingThrottleRequests extends ThrottleRequests
{
    /**
     * @param  Request  $request
     * @param  array<int, object{key: string}>  $limits
     */
    protected function handleRequest($request, Closure $next, array $limits)
    {
        foreach ($limits as $limit) {
            $this->limiter->clear($limit->key);
        }

        return parent::handleRequest($request, $next, $limits);
    }
}
