<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Exceptions;

use RuntimeException;

class RouteNotFoundException extends RuntimeException
{
    /**
     * @param  array<int, string>  $suggestions
     */
    private function __construct(
        public readonly string $reference,
        public readonly array $suggestions,
    ) {
        parent::__construct(sprintf('No route matches `%s`.', $reference));
    }

    /**
     * @param  array<int, string>  $suggestions  Ids of the routes the reference may have meant.
     */
    public static function becauseNoRouteMatches(string $reference, array $suggestions = []): self
    {
        return new self($reference, $suggestions);
    }
}
