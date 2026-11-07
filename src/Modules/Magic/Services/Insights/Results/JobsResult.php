<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type JobEntry array{job: string, queue: string|null, connection: string|null}
 *
 * @extends InsightListResult<JobEntry>
 */
class JobsResult extends InsightListResult {}
