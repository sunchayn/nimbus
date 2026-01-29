<?php

namespace Sunchayn\Nimbus\Modules\Routes\RoutesProcessors\Strategies;

use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;

/**
 * Contract for providing extracted routes to Nimbus.
 *
 * Implementations may extract routes from various sources such as
 * Laravel routes (auto-detection) or OpenAPI specification files.
 */
interface RoutesProcessorContract
{
    /**
     * Get the display name of the processor.
     */
    public function getName(): RoutesProcessingStrategyEnum;

    /**
     * Provides the collection of extracted routes.
     */
    public function process(): ExtractedRoutesCollection;
}
