<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts;

use Illuminate\Routing\Route;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

interface RequestSchemaStrategyContract
{
    /**
     * Attempts to extract a request validation Schema for the given route.
     *
     * Returns a non-empty Schema on success, or null if this strategy does not
     * apply to the given route or cannot produce a usable Schema.
     */
    public function attempt(Route $route): ?Schema;
}
