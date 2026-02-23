<?php

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionFunction;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionInternalException;
use Sunchayn\Nimbus\Modules\Routes\Extractor\SchemaExtractor;
use Sunchayn\Nimbus\Modules\Routes\Factories\ExtractableRouteFactory;
use Sunchayn\Nimbus\Modules\Routes\Services\IgnoredRoutesService;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Throwable;

/**
 * Orchestrates the extraction of validation schemas from Laravel routes.
 *
 * Filters routes by prefix and app-origin, excludes ignored routes,
 * and transforms each route into a structured configuration with
 * extracted validation schemas.
 *
 * By default, only application-defined routes (not from vendor packages)
 * are included. Use `included_prefixes` to opt-in specific package
 * routes, and `excluded_prefixes` to exclude specific app routes.
 *
 * Route extraction is fault-tolerant: if a single route fails to be
 * processed (e.g. no extractable resource segment), it is logged and
 * skipped without affecting the remaining routes.
 */
class ExtractApplicationRoutesAction
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
     * The filtering pipeline:
     * 1. Prefix filter — keep routes matching any of the configured prefixes,
     *    plus any routes whose first URI segment is in `included_prefixes`.
     * 2. App-route check — keep only routes defined in the app (not vendor/),
     *    plus any routes explicitly included via `included_prefixes`.
     * 3. Excluded prefixes — reject routes whose first URI segment matches.
     * 4. Ignored routes — reject routes persisted in the ignore list.
     *
     * @param  array<int, Route>  $routes
     */
    public function execute(array $routes): ExtractedRoutesCollection
    {
        $prefixes = $this->activeApplicationResolver->getRoutesPrefix();
        $includedPrefixes = $this->activeApplicationResolver->getIncludedPrefixes();
        $excludedPrefixes = $this->activeApplicationResolver->getExcludedPrefixes();

        $skippedRoutes = [];

        $configs = collect($routes)
            ->filter(function (Route $route) use ($prefixes, $includedPrefixes): bool {
                if ($includedPrefixes !== [] && in_array($this->getFirstUriSegment($route), $includedPrefixes, true)) {
                    return true;
                }

                if ($prefixes === []) {
                    return true;
                }

                $uri = $route->uri();

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($uri, $prefix) || str_starts_with($uri, '/'.$prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->filter(function (Route $route) use ($includedPrefixes): bool {
                if ($this->isAppRoute($route)) {
                    return true;
                }

                if ($includedPrefixes === []) {
                    return false;
                }

                return in_array($this->getFirstUriSegment($route), $includedPrefixes, true);
            })
            ->reject(function (Route $route) use ($excludedPrefixes): bool {
                if ($excludedPrefixes === []) {
                    return false;
                }

                return in_array($this->getFirstUriSegment($route), $excludedPrefixes, true);
            })
            ->when(
                $this->ignoredRoutesService->hasIgnoredRoutes(),
                fn (Collection $routes) => $routes->reject(fn (Route $route): bool => $this->ignoredRoutesService->isIgnored($route))
            )
            ->values()
            ->map(function (Route $route) use (&$skippedRoutes): ?ExtractedRoute {
                try {
                    return $this->transformRoute($route);
                } catch (RouteExtractionException $exception) {
                    $this->logger->warning(
                        "Skipping route '{$route->uri()}': {$exception->getMessage()}",
                    );

                    $skippedRoutes[] = [
                        'uri' => $route->uri(),
                        'methods' => array_values(array_filter(
                            $route->methods(),
                            fn (string $method): bool => $method !== 'HEAD',
                        )),
                        'reason' => $exception->getMessage(),
                    ];

                    return null;
                }
            })
            ->filter();

        $collection = ExtractedRoutesCollection::make($configs);
        $collection->setSkippedRoutes($skippedRoutes);

        return $collection;
    }

    /**
     * Transforms a Laravel route into a RouteConfig with extracted schema.
     *
     * @throws RouteExtractionException
     */
    protected function transformRoute(Route $route): ExtractedRoute
    {
        // Factory — failure here means the route definition itself is broken → skip
        try {
            $extractableRoute = $this->routeFactory->fromLaravelRoute($route);
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
            );
        }

        // Schema extraction — failure here → show route with error badge
        try {
            $schema = $this->schemaExtractor->extract($extractableRoute);
        } catch (Throwable $throwable) {
            $this->logger->warning(
                "Schema extraction failed for route '{$route->uri()}': {$throwable->getMessage()}",
            );

            $schema = new Schema(
                properties: [],
                extractionError: new RulesExtractionError(throwable: $throwable),
            );
        }

        $methods = Arr::where(
            $route->methods(),
            // We exclude the `HEAD` methods as they don't carry request bodies.
            fn (string $method): bool => $method !== 'HEAD',
        );

        $uri = $route->uri();
        $normalisedUri = str_starts_with($uri, '/') ? $uri : '/'.$uri;

        $endpoint = Endpoint::fromRaw(
            $normalisedUri,
            routesPrefix: $this->findMatchingPrefix($normalisedUri),
            isVersioned: $this->activeApplicationResolver->isVersioned(),
        );

        if ($endpoint->resource === '') {
            $endpoint = new Endpoint(
                version: $endpoint->version,
                resource: '/',
                value: $endpoint->value,
                prefix: $endpoint->prefix,
                shortUriOverride: '/',
            );
        }

        $keywords = array_filter([
            $endpoint->value,
            $endpoint->getShortUri(),
            $route->getName(),
        ]);

        return new ExtractedRoute(
            uri: $endpoint,
            methods: $methods,
            schema: $schema,
            keywords: array_values($keywords),
        );
    }

    /**
     * Determines if a route is defined in the application (not in vendor/).
     *
     * Uses the same ReflectionClass technique as Laravel's
     * RouteListCommand::isVendorRoute().
     */
    private function isAppRoute(Route $route): bool
    {
        try {
            $uses = $route->getAction('uses');

            if ($uses instanceof Closure) {
                $path = (new ReflectionFunction($uses))->getFileName();

                return $path !== false && ! str_starts_with($path, base_path('vendor'));
            }

            if (is_string($uses) && str_contains($uses, 'SerializableClosure')) {
                return true;
            }

            if (is_string($uses) && ($controllerClass = $route->getControllerClass()) !== null) {
                /** @var class-string $controllerClass */
                $path = (new ReflectionClass($controllerClass))->getFileName();

                return $path !== false && ! str_starts_with($path, base_path('vendor'));
            }
        } catch (\ReflectionException) {
            // If the controller class cannot be reflected (e.g. it doesn't exist),
            // treat the route as an app route to avoid silently dropping it.
        }

        return true;
    }

    private function findMatchingPrefix(string $uri): string
    {
        foreach ($this->activeApplicationResolver->getRoutesPrefix() as $prefix) {
            if ($prefix === '') {
                continue;
            }

            if (str_starts_with($uri, $prefix) || str_starts_with($uri, '/'.$prefix)) {
                return $prefix;
            }
        }

        return '';
    }

    private function getFirstUriSegment(Route $route): string
    {
        $uri = ltrim($route->uri(), '/');

        return explode('/', $uri, 2)[0];
    }
}
