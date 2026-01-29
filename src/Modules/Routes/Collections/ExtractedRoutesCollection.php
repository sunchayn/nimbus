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
 * }
 *
 * @extends Collection<array-key, ExtractedRoute>
 */
class ExtractedRoutesCollection extends Collection
{
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
                                ],
                            ),
                        );
                },
            )
            ->toArray();
    }
}
