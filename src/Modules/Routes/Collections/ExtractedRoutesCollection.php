<?php

namespace Sunchayn\Nimbus\Modules\Routes\Collections;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;

/**
 * @phpstan-type RouteDefinitionShape array{
 *     uri: string,
 *     shortUri: string,
 *     methods: string[],
 *     schema: array<string, mixed>,
 *     extractionError: string|null,
 *     prefix: string,
 * }
 *
 * @extends Collection<array-key, ExtractedRoute>
 */
class ExtractedRoutesCollection extends Collection
{
    /** @var array<int, array{uri: string, methods: string[], reason: string}> */
    private array $skippedRoutes = [];

    /**
     * @param  array<int, array{uri: string, methods: string[], reason: string}>  $skippedRoutes
     */
    public function setSkippedRoutes(array $skippedRoutes): self
    {
        $this->skippedRoutes = $skippedRoutes;

        return $this;
    }

    /**
     * @return array<int, array{uri: string, methods: string[], reason: string}>
     */
    public function getSkippedRoutes(): array
    {
        return $this->skippedRoutes;
    }

    public function hasSkippedRoutes(): bool
    {
        return $this->skippedRoutes !== [];
    }

    /**
     * @return array<string, array<string, RouteDefinitionShape[]>>
     */
    public function toFrontendArray(): array
    {
        /** @var Collection<string, self> $groupedByVersion */
        $groupedByVersion = $this->groupBy(static fn (ExtractedRoute $extractedRoute): string => $extractedRoute->uri->version);

        return $groupedByVersion
            ->map(
                static function (self $group): Collection {
                    /** @var Collection<string, self> $groupedByResource */
                    $groupedByResource = $group
                        ->groupBy(static fn (ExtractedRoute $extractedRoute): string => $extractedRoute->uri->resource);

                    return $groupedByResource
                        ->map(
                            static fn (self $group): Collection => $group->map(
                                static fn (ExtractedRoute $extractedRoute): array => [
                                    'uri' => $extractedRoute->uri->value,
                                    'shortUri' => Str::replaceStart('/', '', $extractedRoute->uri->getShortUri()),
                                    'methods' => $extractedRoute->methods,
                                    'schema' => $extractedRoute->schema->toJsonSchema(),
                                    'extractionError' => $extractedRoute->schema->extractionError?->toHtml(),
                                    'metadata' => $extractedRoute->metadata,
                                    'keywords' => $extractedRoute->keywords,
                                    'prefix' => $extractedRoute->uri->prefix,
                                ],
                            ),
                        );
                },
            )
            ->toArray();
    }
}
