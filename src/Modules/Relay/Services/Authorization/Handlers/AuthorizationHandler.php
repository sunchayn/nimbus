<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;

/**
 * Contract for authorization handlers that manage their own authorization logic.
 */
interface AuthorizationHandler
{
    /**
     * Apply authorization to the pending request.
     * Each handler manages its own authorization logic.
     */
    public function authorize(PendingRequest $pendingRequest): PendingRequest;
}
