<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type Log array{level: string, message: string, context: array<string, mixed>}
 *
 * @extends InsightListResult<Log>
 */
class LogsResult extends InsightListResult {}
