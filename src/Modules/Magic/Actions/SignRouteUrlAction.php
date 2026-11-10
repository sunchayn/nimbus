<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Actions;

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Sunchayn\Nimbus\Modules\Magic\Exceptions\UnsignableUrlException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Signs the URL of a route that uses the signed middleware, so a request can reach it.
 *
 * @final
 */
class SignRouteUrlAction
{
    public function __construct(
        private readonly Router $router,
        private readonly UrlGenerator $urlGenerator,
    ) {}

    /**
     * @throws UnsignableUrlException
     */
    public function execute(string $method, string $url): string
    {
        $request = Request::create($url, $method);

        $route = $this->matchRoute($request);

        $signedMiddleware = $this->findSignedMiddleware($route);

        if ($signedMiddleware === null) {
            throw UnsignableUrlException::notSigned();
        }

        $name = $route->getName();

        if ($name === null) {
            throw UnsignableUrlException::unnamed();
        }

        $query = $request->query();

        unset($query['signature'], $query['expires']);

        // The signature covers the host, so it must be the host the request will be sent to.
        $generator = clone $this->urlGenerator;
        $generator->forceRootUrl($request->getSchemeAndHttpHost()); // @phpstan-ignore-line useOrigin does not exist in Laravel 10.

        return $generator->signedRoute(
            name: $name,
            parameters: [...$route->originalParameters(), ...$query],
            absolute: ! in_array('relative', explode(',', explode(':', $signedMiddleware, 2)[1] ?? ''), true),
        );
    }

    /**
     * @throws UnsignableUrlException
     */
    private function matchRoute(Request $request): Route
    {
        try {
            return $this->router->getRoutes()->match($request);
        } catch (HttpException) {
            throw UnsignableUrlException::noRoute();
        }
    }

    private function findSignedMiddleware(Route $route): ?string
    {
        foreach ($this->router->gatherRouteMiddleware($route) as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, ValidateSignature::class)) {
                return $middleware;
            }
        }

        return null;
    }
}
