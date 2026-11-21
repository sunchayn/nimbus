<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationRoute;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;

/** @final */
class ListRoutesAction
{
    public const DEFAULT_LIMIT = 50;

    public const MAX_LIMIT = 200;

    public function __construct(private readonly ExtractRoutesByApplicationAction $extractRoutesByApplicationAction) {}

    /**
     * @return array{routes: array<int, array<string, mixed>>, total: int, truncated: bool}
     *
     * @throws McpToolException
     */
    public function execute(
        ?string $search = null,
        ?string $method = null,
        ?string $prefix = null,
        int $limit = self::DEFAULT_LIMIT,
        ?string $application = null,
    ): array {
        $limit = max(1, min($limit, self::MAX_LIMIT));

        $routesByApplication = $this->extractRoutesByApplicationAction->execute(application: $application);

        $includeApplication = $routesByApplication->isMultiApplication;

        $matchingRoutes = $routesByApplication
            ->perMethod()
            ->filter(fn (ApplicationRoute $applicationRoute): bool => $this->matches(
                route: $applicationRoute->route,
                search: $search,
                method: $method,
                prefix: $prefix,
            ))
            ->values();

        return [
            'routes' => $matchingRoutes
                ->take($limit)
                ->toBase()
                ->map(fn (ApplicationRoute $applicationRoute): array => $this->summarize(
                    applicationRoute: $applicationRoute,
                    includeApplication: $includeApplication,
                ))
                ->values()
                ->all(),
            'total' => $matchingRoutes->count(),
            'truncated' => $matchingRoutes->count() > $limit,
        ];
    }

    private function matches(
        ExtractedRoute $route,
        ?string $search,
        ?string $method,
        ?string $prefix,
    ): bool {
        if ($method !== null && strcasecmp($method, $route->method()) !== 0) {
            return false;
        }

        if ($prefix !== null && ! str_starts_with(trim($route->uri->value, '/'), trim($prefix, '/'))) {
            return false;
        }

        if ($search === null || $search === '') {
            return true;
        }

        $keywords = implode(' ', $route->keywords);

        return str_contains(strtolower("{$route->id()} {$keywords}"), strtolower($search));
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize(ApplicationRoute $applicationRoute, bool $includeApplication): array
    {
        $route = $applicationRoute->route;

        return [
            ...$this->listApplication(applicationRoute: $applicationRoute, includeApplication: $includeApplication),
            'id' => $route->id(),
            'method' => $route->method(),
            'uri' => $route->uri->value,
            'name' => $route->name(),
            'required' => $route->schema->getRequiredProperties(),
            'hasExtractionError' => $route->schema->extractionError instanceof RulesExtractionError,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function listApplication(ApplicationRoute $applicationRoute, bool $includeApplication): array
    {
        if (! $includeApplication) {
            return [];
        }

        return ['application' => $applicationRoute->application];
    }
}
