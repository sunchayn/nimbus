<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Routes\DataTransferObjects\ExtractedRoute;

/**
 * A route with the key of the application it was extracted for.
 */
final readonly class ApplicationRoute
{
    public function __construct(
        public string $application,
        public ExtractedRoute $route,
    ) {}
}
