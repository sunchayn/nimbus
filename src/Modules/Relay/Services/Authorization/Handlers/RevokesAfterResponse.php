<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

/**
 * Implemented by handlers that create something for one request and must remove it afterwards.
 */
interface RevokesAfterResponse
{
    public function revoke(): void;
}
