<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type HttpEntry array{method: string, url: string, status: int|null, duration_ms: float|null, offset_ms: float|null}
 *
 * @extends InsightListResult<HttpEntry>
 */
class HttpResult extends InsightListResult {}
