<?php

namespace Sunchayn\Nimbus\Modules\Routes\Collections;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\ValueObjects\Endpoint;

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
     * Routes are grouped by version, then into folders.
     * A folder is the route resource, or a path of URI segments when nested folders are enabled.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function toFrontendArray(bool $shouldNestFolders = false): array
    {
        /** @var Collection<string, self> $groupedByVersion */
        $groupedByVersion = $this->groupBy(static fn (ExtractedRoute $extractedRoute): string => $extractedRoute->uri->version);

        return $groupedByVersion
            ->map(static fn (self $routesInVersion): array => $routesInVersion->toFolders($shouldNestFolders))
            ->toArray();
    }

    /**
     * @param  string[]  $parentPath
     * @return array<int, array<string, mixed>>
     */
    private function toFolders(bool $shouldNestFolders, array $parentPath = []): array
    {
        $foldersOf = static fn (ExtractedRoute $extractedRoute): array => $shouldNestFolders
            ? $extractedRoute->uri->getFolders()
            : [$extractedRoute->uri->resource];

        /** @var Collection<string, self> $groupedByFolder */
        $groupedByFolder = $this->groupBy(
            static fn (ExtractedRoute $extractedRoute): string => $foldersOf($extractedRoute)[count($parentPath)],
        );

        return $groupedByFolder
            ->sortKeysUsing(function (string $keyA, string $keyB): int {
                // The root always comes first, everything else is sorted alphabetically.
                return match (true) {
                    $keyA === Endpoint::ROOT_RESOURCE => -1,
                    $keyB === Endpoint::ROOT_RESOURCE => 1,
                    default => strcmp($keyA, $keyB),
                };
            })
            ->toBase()
            ->map(function (self $routesInFolder, string $name) use ($parentPath, $foldersOf, $shouldNestFolders): array {
                $path = [...$parentPath, $name];

                // Routes ending at this folder stay here and the deeper ones go to the children.
                $isOwnRoute = static fn (ExtractedRoute $extractedRoute): bool => count($foldersOf($extractedRoute)) === count($path);

                return [
                    'name' => $name,
                    'path' => implode('/', $path),
                    'routes' => $routesInFolder->filter($isOwnRoute)->toBase()->map(
                        static fn (ExtractedRoute $extractedRoute): array => [
                            'uri' => $extractedRoute->uri->value,
                            'shortUri' => Str::replaceStart('/', '', $extractedRoute->uri->getShortUri()),
                            'methods' => $extractedRoute->methods,
                            'schema' => $extractedRoute->schema->toJsonSchema(),
                            'extractionError' => $extractedRoute->schema->extractionError?->toHtml(),
                            'metadata' => $extractedRoute->metadata,
                            'keywords' => $extractedRoute->keywords,
                        ],
                    )->values()->all(),
                    'children' => $routesInFolder->reject($isOwnRoute)->toFolders($shouldNestFolders, $path),
                ];
            })
            ->values()
            ->all();
    }
}
