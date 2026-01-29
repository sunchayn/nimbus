<?php

namespace Sunchayn\Nimbus\Modules\Routes\Exceptions;

use Throwable;

interface RoutesProcessingException extends Throwable
{
    public function getFrontEndIdentifier(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
