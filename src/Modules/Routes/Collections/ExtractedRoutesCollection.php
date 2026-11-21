<?php

namespace Sunchayn\Nimbus\Modules\Routes\Collections;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;
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
    private const AMBIGUOUS_SUGGESTIONS_LIMIT = 5;

    private const NEAREST_SUGGESTIONS_LIMIT = 3;

    private const NEAREST_MIN_SIMILARITY = 50;

    /**
     * One route per method, so every route has a single id.
     * Each route is split with `ExtractedRoute::splitByMethod`.
     */
    public function perMethod(): self
    {
        $routes = $this
            ->flatMap(static fn (ExtractedRoute $extractedRoute): array => $extractedRoute->splitByMethod())
            ->values()
            ->all();

        return new self($routes);
    }

    /**
     * The routes, one per method, that a reference points to.
     */
    public function matching(string $reference): self
    {
        return $this
            ->perMethod()
            ->filter(static fn (ExtractedRoute $extractedRoute): bool => $extractedRoute->isReferencedBy(trim($reference)))
            ->values();
    }

    /**
     * Finds the route a reference points to, by its id, by its name, or by its URI when only one method uses it.
     *
     * @throws RouteNotFoundException
     */
    public function findRouteOrFail(string $reference): ExtractedRoute
    {
        $reference = trim($reference);

        $routes = $this->perMethod();

        $matches = $routes->matching($reference);

        if ($matches->count() === 1) {
            return $matches->firstOrFail();
        }

        throw RouteNotFoundException::becauseNoRouteMatches(
            reference: $reference,
            suggestions: $this->listSuggestions(matches: $matches, routes: $routes, reference: $reference),
        );
    }

    /**
     * An ambiguous reference suggests the routes it matches. An unknown one suggests the nearest ids.
     *
     * @param  Collection<int, ExtractedRoute>  $matches
     * @return array<int, string>
     */
    private function listSuggestions(Collection $matches, self $routes, string $reference): array
    {
        if ($matches->isNotEmpty()) {
            return $matches
                ->take(self::AMBIGUOUS_SUGGESTIONS_LIMIT)
                ->map(static fn (ExtractedRoute $extractedRoute): string => $extractedRoute->id())
                ->values()
                ->all();
        }

        return $routes
            ->toBase()
            ->mapWithKeys(static function (ExtractedRoute $extractedRoute) use ($reference): array {
                similar_text(strtolower($reference), strtolower($extractedRoute->id()), $percent);

                return [$extractedRoute->id() => $percent];
            })
            ->filter(static fn (float $percent): bool => $percent >= self::NEAREST_MIN_SIMILARITY)
            ->sortDesc()
            ->keys()
            ->take(self::NEAREST_SUGGESTIONS_LIMIT)
            ->all();
    }

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
