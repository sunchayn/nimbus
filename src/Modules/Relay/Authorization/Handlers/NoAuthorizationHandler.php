<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;

/**
 * Authorization handler that performs no authorization.
 *
 * Used when requests should be sent without any authentication headers
 * or session forwarding, allowing unauthenticated API testing.
 */
class NoAuthorizationHandler implements AuthorizationHandler
{
    /**
     * Applies no authorization to the request.
     *
     * This handler intentionally does nothing, allowing requests to be
     * sent without any authentication mechanisms.
     */
    public function authorize(PendingRequest $pendingRequest): PendingRequest
    {
        return $pendingRequest;
    }
}
