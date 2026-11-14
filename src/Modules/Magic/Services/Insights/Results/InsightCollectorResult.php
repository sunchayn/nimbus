<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * What one insight collector gathered during a request.
 *
 * Each collector returns its own child with typed properties, and `InsightData` holds one slot per child.
 */
abstract class InsightCollectorResult {}
