<?php

namespace Sunchayn\Nimbus\Modules\Routes\RoutesProcessors;

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

        $lookup = $applicationRoutesBySignature->all();

        foreach ($externalSourceRoutes as $externalSourceRoute) {
            $routeSignatures = $externalSourceRoute->getRouteSignatures();

            $matchesApplication = $this->signaturesExist($routeSignatures, $lookup);

            $signatures = [...$signatures, ...array_values($routeSignatures)];

            $routes[] = new ExtractedRoute(
                uri: $externalSourceRoute->uri,
                methods: $externalSourceRoute->methods,
                schema: $externalSourceRoute->schema,
                metadata: [
                    ...$externalSourceRoute->metadata,
                    'isMissingImplementation' => ! $matchesApplication,
                    'isUndocumented' => false,
                ],
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
     * @param  array<string, mixed>  $lookup  Signature lookup table
     */
    protected function signaturesExist(array $signatures, array $lookup): bool
    {
        foreach ($signatures as $signature) {
            if (array_key_exists($signature, $lookup)) {
                return true;
            }
        }

        return false;
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
            ->map(fn (ExtractedRoute $extractedRoute): \Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute => new ExtractedRoute(
                uri: $extractedRoute->uri,
                methods: $extractedRoute->methods,
                schema: $extractedRoute->schema,
                metadata: [
                    'isMissingImplementation' => false,
                    'isUndocumented' => true,
                ],
            ))
            ->values()
            ->all();
    }
}
