<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Services\RoutesProcessors\Strategies;

use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RoutesProcessingException;

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
     *
     * @throws RoutesProcessingException
     */
    public function process(): ExtractedRoutesCollection;
}
