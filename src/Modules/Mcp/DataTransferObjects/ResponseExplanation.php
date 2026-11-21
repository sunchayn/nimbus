<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

/**
 * The likely cause of a response and the evidence that supports it.
 */
class ResponseExplanation
{
    /**
     * @param  array<int, string>  $evidence
     */
    public function __construct(
        public readonly string $cause,
        public readonly array $evidence = [],
    ) {}
}
