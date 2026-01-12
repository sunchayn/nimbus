<?php

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionInternalException;
use Sunchayn\Nimbus\Modules\Routes\Extractor\SchemaExtractor;
use Sunchayn\Nimbus\Modules\Routes\Factories\ExtractableRouteFactory;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Throwable;

/**
 * Orchestrates the extraction of validation schemas from Laravel routes.
 *
 * Filters routes by prefix, excludes ignored routes, and transforms each
 * route into a structured configuration with extracted validation schemas.
 */
class ExtractRoutesAction
{
    public function __construct(
        protected SchemaExtractor $schemaExtractor,
        protected ExtractableRouteFactory $routeFactory,
        protected IgnoredRoutesService $ignoredRoutesService,
        protected ActiveApplicationResolver $activeApplicationResolver,
        protected LoggerInterface $logger,
    ) {}

    /**
     * Processes application routes and extracts schemas.
     *
     * @param  array<int, \Illuminate\Routing\Route>  $routes
     */
    public function execute(array $routes): ExtractedRoutesCollection
    {
        $prefix = $this->activeApplicationResolver->getRoutesPrefix();

        $configs = collect($routes)
            ->filter(function (Route $route) use ($prefix): bool {
                $uri = $route->uri();

                return str_starts_with($uri, $prefix) || str_starts_with($uri, '/'.$prefix);
            })
            ->when(
                $this->ignoredRoutesService->hasIgnoredRoutes(),
                fn (Collection $routes) => $routes->reject(fn (Route $route): bool => $this->ignoredRoutesService->isIgnored($route))
            )
            ->values()
            ->map(fn (Route $route): \Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute => $this->transformRoute($route));

        return ExtractedRoutesCollection::make($configs);
    }

    /**
     * Transforms a Laravel route into a RouteConfig with extracted schema.
     *
     * @throws RouteExtractionException
     */
    protected function transformRoute(Route $route): ExtractedRoute
    {
        try {
            $extractableRoute = $this->routeFactory->fromLaravelRoute($route);

            $schema = $this->schemaExtractor->extract($extractableRoute);
        } catch (Throwable $throwable) {
            $this->logger->error(
                $throwable->getMessage(),
                context: [
                    'trace' => $throwable->getTraceAsString(),
                ],
            );

            throw RouteExtractionInternalException::forRoute(
                throwable: $throwable,
                routeUri: $route->uri(),
                routeMethods: $route->methods(),
                controllerClass: $extractableRoute->controllerClass ?? null,
                controllerMethod: $extractableRoute->controllerMethod ?? null,
            );
        }

        $methods = Arr::where(
            $route->methods(),
            // We exclude the `HEAD` methods as they don't carry request bodies.
            fn (string $method): bool => $method !== 'HEAD',
        );

        return new ExtractedRoute(
            uri: Endpoint::fromRaw(
                $route->uri(),
                routesPrefix: $this->activeApplicationResolver->getRoutesPrefix(),
                isVersioned: $this->activeApplicationResolver->isVersioned(),
            ),
            methods: $methods,
            schema: $schema,
        );
    }
}
