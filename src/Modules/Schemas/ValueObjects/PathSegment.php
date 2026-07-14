<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\ValueObjects;

class PathSegment
{
    public function __construct(
        public readonly string $value,
        public readonly bool $isLeaf = true,
    ) {}

    public function isArray(): bool
    {
        return $this->value === '*';
    }
}
