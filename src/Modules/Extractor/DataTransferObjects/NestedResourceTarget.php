<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\DataTransferObjects;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Represents a nested JsonResource target resolved from AST analysis.
 */
final readonly class NestedResourceTarget
{
    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public function __construct(
        public string $resourceClass,
        public bool $isCollection = false,
    ) {}
}
