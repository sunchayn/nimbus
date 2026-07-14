<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\DataTransferObjects;

/**
 * @codeCoverageIgnore a DTO with no behavior.
 */
readonly class IgnoreRouteErrorData
{
    /**
     * @param  string[]  $methods
     */
    public function __construct(
        public string $uri,
        public array $methods,
    ) {}
}
