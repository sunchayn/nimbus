<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response\Contracts;

use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

interface ResponseSchemaStrategyContract
{
    /**
     * Attempts to extract a Schema for the given route data.
     *
     * Returns a non-empty Schema on success, or null if this strategy does not
     * apply to the given data or cannot produce a usable Schema.
     */
    public function attempt(Route $route): ?Schema;
}
