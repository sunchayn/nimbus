<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationGlobalHeaders;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationRoute;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\ApplicationScope;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;

/** @final */
class DescribeRouteAction
{
    public function __construct(
        private readonly ExtractRoutesByApplicationAction $extractRoutesByApplicationAction,
        private readonly ApplicationScope $applicationScope,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws McpToolException
     */
    public function execute(string $route, ?string $application = null): array
    {
        $routesByApplication = $this->extractRoutesByApplicationAction->execute(application: $application);

        $applicationRoute = $routesByApplication->findRouteOrFail($route);

        $extractedRoute = $applicationRoute->route;

        $schema = $extractedRoute->schema;

        $generatedHeaders = $this->applicationScope->run(
            application: $applicationRoute->application,
            callback: fn (ActiveApplicationResolver $resolver): array => ApplicationGlobalHeaders::fromResolver($resolver)->generated,
        );

        return [
            ...$this->listApplication(
                applicationRoute: $applicationRoute,
                isMultiApplication: $routesByApplication->isMultiApplication,
            ),
            'id' => $extractedRoute->id(),
            'method' => $extractedRoute->method(),
            'uri' => $extractedRoute->uri->value,
            'name' => $extractedRoute->name(),
            'required' => $schema->getRequiredProperties(),
            'generatedHeaders' => $generatedHeaders,
            'schema' => $schema->toJsonSchema(),
            'extractionError' => $this->describeExtractionError(error: $schema->extractionError),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function listApplication(ApplicationRoute $applicationRoute, bool $isMultiApplication): array
    {
        if (! $isMultiApplication) {
            return [];
        }

        return ['application' => $applicationRoute->application];
    }

    private function describeExtractionError(?RulesExtractionError $error): ?string
    {
        if (! $error instanceof RulesExtractionError) {
            return null;
        }

        return strip_tags($error->toHtml());
    }
}
