<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type CacheEntry array{operation: 'hit'|'miss'|'write'|'forget', key: string}
 *
 * @extends InsightListResult<CacheEntry>
 */
class CacheResult extends InsightListResult {}
