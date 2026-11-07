<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors;

use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results\InsightCollectorResult;

/**
 * One kind of data the insight collector gathers during a request.
 */
interface InsightCollectorContract
{
    /**
     * Registers the listeners this collector needs.
     */
    public function start(): void;

    /**
     * Returns what this collector gathered, as the result class it owns.
     */
    public function collect(): InsightCollectorResult;
}
