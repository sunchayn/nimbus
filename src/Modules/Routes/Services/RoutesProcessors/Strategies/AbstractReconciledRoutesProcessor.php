<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies;

use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\RouteReconciliationService;

/**
 * Base strategy for route processors that reconcile external routes with auto-detected ones.
 *
 * This abstract class enforces a pattern where routes from an external source
 * (like OpenAPI or Postman) are always reconciled against the application's
 * actual auto-detected routes to ensure accuracy and identify gaps.
 */
abstract class AbstractReconciledRoutesProcessor implements RoutesProcessorContract
{
    public function __construct(
        protected AutoDetectRoutesProcessor $autoDetectRoutesProcessor,
        protected RouteReconciliationService $routeReconciliationService,
    ) {}

    /**
     * Retrieve routes from the external source.
     */
    abstract protected function getRoutesFromExternalSource(): ExtractedRoutesCollection;

    public function process(): ExtractedRoutesCollection
    {
        return $this->routeReconciliationService->execute(
            externalSourceRoutes: $this->getRoutesFromExternalSource(),
            autoDetectedRoutes: $this->autoDetectRoutesProcessor->process(),
        );
    }
}
