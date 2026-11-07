<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type Query array{sql: string, bindings: list<mixed>, time_ms: float, offset_ms: float, connection: string}
 *
 * @extends InsightListResult<Query>
 */
class QueriesResult extends InsightListResult {}
