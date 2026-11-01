<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Events;

/**
 * Dispatched when an extraction step resolves the source file of a class.
 */
class ClassFileResolved
{
    public function __construct(
        public readonly string $path,
    ) {}
}
