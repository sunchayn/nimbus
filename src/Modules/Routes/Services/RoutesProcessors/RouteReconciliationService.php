<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;

/**
 * Reconciles routes between an external source and the application's implementation.
 *
 * This service compares a "source of truth" (e.g., OpenAPI spec) against the actual
 * application routes to identify discrepancies:
 * - Missing Implementation: Routes defined in the source but not found in the app.
 * - Undocumented: Routes found in the app but missing from the source.
 */
class RouteReconciliationService
{
    /**
     * Reconcile the external source routes with the application's auto-detected routes.
     *
     * @param  ExtractedRoutesCollection  $externalSourceRoutes  The reference routes (e.g., OpenAPI)
     * @param  ExtractedRoutesCollection  $autoDetectedRoutes  The actual application routes.
     */
    public function execute(
        ExtractedRoutesCollection $externalSourceRoutes,
        ExtractedRoutesCollection $autoDetectedRoutes,
    ): ExtractedRoutesCollection {
        $routes = [];
        $signatures = [];

        $applicationRoutesBySignature = $autoDetectedRoutes
            ->flatMap(fn (ExtractedRoute $extractedRoute) => collect($extractedRoute->getRouteSignatures())
                ->mapWithKeys(fn (string $signature): array => [$signature => $extractedRoute])
            );

        /** @var array<string, ExtractedRoute> $lookup */
        $lookup = $applicationRoutesBySignature->all();

        foreach ($externalSourceRoutes as $externalSourceRoute) {
            $routeSignatures = $externalSourceRoute->getRouteSignatures();

            $matchingRouteInApplication = $this->findMatchingRoute($routeSignatures, $lookup);

            $isMatching = $matchingRouteInApplication instanceof \Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;

            $signatures = [...$signatures, ...array_values($routeSignatures)];

            $routes[] = new ExtractedRoute(
                uri: $externalSourceRoute->uri,
                methods: $externalSourceRoute->methods,
                schema: $externalSourceRoute->schema,
                metadata: [
                    ...$externalSourceRoute->metadata,
                    'isMissingImplementation' => ! $isMatching,
                    'isUndocumented' => false,
                ],
                keywords: array_merge(
                    $matchingRouteInApplication->keywords ?? [],
                    $externalSourceRoute->keywords,
                ),
            );
        }

        $routes = array_merge(
            $routes,
            $this->getAbsentRoutes($applicationRoutesBySignature, $signatures),
        );

        return ExtractedRoutesCollection::make($routes);
    }

    /**
     * @param  array<string, string>  $signatures  Method => Signature map
     * @param  array<string, ExtractedRoute>  $lookup  Signature lookup table
     */
    protected function findMatchingRoute(array $signatures, array $lookup): ?ExtractedRoute
    {
        $matchingSignature = Arr::first(
            $signatures,
            fn (string $signature): bool => array_key_exists($signature, $lookup),
        );

        if (! $matchingSignature) {
            return null;
        }

        return $lookup[$matchingSignature];
    }

    /**
     * Return the list of routes that are auto-detected but absent in the external source.
     *
     * @param  Collection<string, ExtractedRoute>  $routesBySignature
     * @param  string[]  $matchedSignatures
     * @return ExtractedRoute[]
     */
    protected function getAbsentRoutes(Collection $routesBySignature, array $matchedSignatures): array
    {
        $matched = array_flip($matchedSignatures);

        return $routesBySignature
            ->reject(fn ($_, string $signature): bool => isset($matched[$signature]))
            ->map(fn (ExtractedRoute $extractedRoute): ExtractedRoute => new ExtractedRoute(
                uri: $extractedRoute->uri,
                methods: $extractedRoute->methods,
                schema: $extractedRoute->schema,
                metadata: [
                    'isMissingImplementation' => false,
                    'isUndocumented' => true,
                ],
                keywords: $extractedRoute->keywords,
            ))
            ->values()
            ->all();
    }
}
