<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Illuminate\Support\Collection;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;

/**
 * The extracted routes of one or more applications, by application key.
 */
final readonly class RoutesByApplication
{
    /**
     * @param  array<string, ExtractedRoutesCollection>  $byApplication
     * @param  bool  $isMultiApplication  Whether more than one application is configured, even if only one was extracted.
     */
    public function __construct(
        private array $byApplication,
        public bool $isMultiApplication,
    ) {}

    /**
     * One entry per route and method.
     *
     * @return Collection<int, ApplicationRoute>
     */
    public function perMethod(): Collection
    {
        return collect($this->byApplication)
            ->flatMap(
                static fn (ExtractedRoutesCollection $routes, string $application): array => $routes
                    ->perMethod()
                    ->toBase()
                    ->map(static fn (ExtractedRoute $route): ApplicationRoute => new ApplicationRoute($application, $route))
                    ->all(),
            )
            ->values();
    }

    /**
     * Finds the route a reference points to.
     * A reference that matches routes in several applications is ambiguous.
     *
     * @throws RouteNotFoundException
     * @throws McpToolException
     */
    public function findRouteOrFail(string $reference): ApplicationRoute
    {
        $reference = trim($reference);

        $matchingApplications = collect($this->byApplication)
            ->filter(static fn (ExtractedRoutesCollection $routes): bool => $routes->matching($reference)->isNotEmpty())
            ->keys()
            ->map(strval(...))
            ->values();

        if ($matchingApplications->count() > 1) {
            throw McpToolException::becauseRouteIsAmbiguous(
                reference: $reference,
                applications: $matchingApplications->all(),
            );
        }

        $application = $matchingApplications->first();

        if ($application === null) {
            $this->failBecauseNoRouteMatches(reference: $reference);
        }

        return new ApplicationRoute(
            $application,
            $this->byApplication[$application]->findRouteOrFail($reference),
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    private function failBecauseNoRouteMatches(string $reference): never
    {
        // The merged routes have no match, so this throws with the nearest ids as suggestions.
        $this->mergeRoutes()->findRouteOrFail($reference);

        throw RouteNotFoundException::becauseNoRouteMatches(reference: $reference);
    }

    /**
     * All the routes in one collection, used to suggest the nearest ids of an unknown reference.
     */
    private function mergeRoutes(): ExtractedRoutesCollection
    {
        return new ExtractedRoutesCollection(
            collect($this->byApplication)->flatten(1)->all(),
        );
    }
}
