<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies;

use Illuminate\Routing\Router;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Actions\ExtractApplicationRoutesAction;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;

/**
 * Provides routes by extracting them from Laravel's registered routes.
 *
 * This is the default provider that uses auto-detection to extract
 * routes and their validation schemas from Laravel's route registry.
 */
class AutoDetectRoutesProcessor implements RoutesProcessorContract
{
    public function __construct(
        protected ExtractApplicationRoutesAction $extractRoutesAction,
        protected Router $router,
    ) {}

    public function getName(): RoutesProcessingStrategyEnum
    {
        return RoutesProcessingStrategyEnum::AutoDetect;
    }

    public function process(): ExtractedRoutesCollection
    {
        return $this->extractRoutesAction->execute(
            routes: $this->router->getRoutes()->getRoutes(),
        );
    }
}
