<?php

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Sunchayn\Nimbus\Modules\Magic\ValueObjects\NimbusHeaders;
use Throwable;

/**
 * Authenticates the request as the user named in the actor header.
 *
 * It runs on `RouteMatched`, which fires before route middleware, so `auth` and `can` see the user.
 * The user is only set on the guard. No session, cookie, or database write happens.
 */
class ActorHeaderListener
{
    public function handle(RouteMatched $routeMatched): void
    {
        $nimbusHeaders = NimbusHeaders::fromRequest($routeMatched->request);

        if (! $nimbusHeaders->hasActor()) {
            return;
        }

        $guardName = (string) $nimbusHeaders->actorGuard;

        try {
            $guard = Auth::guard($guardName);

            if (! method_exists($guard, 'getProvider')) {
                throw new \RuntimeException('The guard does not expose a provider.');
            }

            $user = $guard->getProvider()->retrieveById($nimbusHeaders->actorKey);

            if ($user === null) {
                throw new \RuntimeException('The user was not found.');
            }

            Auth::shouldUse($guardName);

            $guard->setUser($user);
        } catch (Throwable $throwable) {
            Log::debug('Nimbus ignored the actor header.', [
                'guard' => $guardName,
                'reason' => $throwable->getMessage(),
            ]);
        }
    }
}
