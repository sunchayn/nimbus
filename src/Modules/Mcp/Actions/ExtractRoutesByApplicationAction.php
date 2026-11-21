<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Illuminate\Contracts\Container\Container;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\RoutesByApplication;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\ApplicationScope;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;

/**
 * Extracts the routes of one application, or of every configured application.
 * Each extraction runs with its application active, so the processor and the snapshot follow it.
 *
 * @final
 */
class ExtractRoutesByApplicationAction
{
    public function __construct(
        private readonly Container $container,
        private readonly ApplicationScope $applicationScope,
    ) {}

    /**
     * @throws McpToolException
     * @throws RoutesProcessingException
     */
    public function execute(?string $application = null): RoutesByApplication
    {
        $configured = $this->applicationScope->listKeys();

        $keys = $application === null ? $configured : [$application];

        $routes = [];

        foreach ($keys as $key) {
            $routes[$key] = $this->applicationScope->run(
                application: $key,
                callback: fn (): ExtractedRoutesCollection => $this->container->make(ExtractRoutesAction::class)->execute(),
            );
        }

        return new RoutesByApplication(
            byApplication: $routes,
            isMultiApplication: count($configured) > 1,
        );
    }
}
