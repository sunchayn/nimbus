<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractOpenApiRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\OpenApiPackageNotInstalledException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\OpenApiParsingException;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\RouteReconciliationService;
use Throwable;

/**
 * Provides routes by parsing OpenAPI specification files.
 *
 * This provider reads configured OpenAPI files per version and extracts
 * routes with their request schemas. It also merges with Laravel's native
 * routes to ensure all routes are available and properly flagged.
 */
class OpenAPISchemaRoutesProcessor extends AbstractReconciledRoutesProcessor
{
    private const DEFAULT_VERSION = 'default';

    public function __construct(
        protected ActiveApplicationResolver $activeApplicationResolver,
        protected ExtractOpenApiRoutesAction $extractOpenApiRoutesAction,
        AutoDetectRoutesProcessor $autoDetectRoutesProcessor,
        RouteReconciliationService $routeReconciliationService,
    ) {
        parent::__construct($autoDetectRoutesProcessor, $routeReconciliationService);

        $this->ensureOpenApiPackageIsInstalled();
    }

    public function getName(): RoutesProcessingStrategyEnum
    {
        return RoutesProcessingStrategyEnum::OpenAPI;
    }

    protected function getRoutesFromExternalSource(): ExtractedRoutesCollection
    {
        $routes = collect($this->activeApplicationResolver->getOpenApiFiles())
            ->flatMap(function (string $filepath, string $version): array {
                $version = $this->activeApplicationResolver->isVersioned()
                    ? $version
                    : self::DEFAULT_VERSION;

                return $this->extractOpenApiRoutesAction->execute(
                    openapi: $this->parseOpenApiFile($filepath),
                    version: $version,
                );
            });

        return ExtractedRoutesCollection::make($routes);
    }

    /**
     * @throws OpenApiPackageNotInstalledException
     */
    private function ensureOpenApiPackageIsInstalled(): void
    {
        if (! class_exists(Reader::class)) {
            throw new OpenApiPackageNotInstalledException;
        }
    }

    /**
     * @throws OpenApiParsingException
     */
    private function parseOpenApiFile(string $filePath): OpenApi
    {
        if (! file_exists($filePath)) {
            throw new OpenApiParsingException(
                filePath: $filePath,
                parsingError: 'File does not exist.',
            );
        }

        try {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            return match ($extension) {
                'json' => Reader::readFromJsonFile($filePath),
                'yaml', 'yml' => Reader::readFromYamlFile($filePath),
                default => throw new OpenApiParsingException(
                    filePath: $filePath,
                    parsingError: sprintf('Unsupported file extension: %s. Use .json, .yaml, or .yml.', $extension),
                ),
            };
        } catch (OpenApiParsingException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            throw new OpenApiParsingException(
                filePath: $filePath,
                parsingError: $throwable->getMessage(),
                previous: $throwable,
            );
        }
    }
}
