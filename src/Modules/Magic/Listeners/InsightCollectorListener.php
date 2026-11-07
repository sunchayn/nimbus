<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Illuminate\Routing\Events\RouteMatched;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightsService;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;

/**
 * Starts the insight collector for a request that carries a valid `X-Nimbus-Insight` id.
 */
class InsightCollectorListener
{
    public function handle(RouteMatched $routeMatched): void
    {
        $id = $routeMatched->request->header(InsightStore::HEADER);

        if (! InsightStore::isValidId($id)) {
            return;
        }

        app(InsightsService::class)->start((string) $id);
    }
}
