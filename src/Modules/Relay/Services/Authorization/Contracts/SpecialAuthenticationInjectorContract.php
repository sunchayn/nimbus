<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\PendingRequest;

/**
 * Contract for injecting authentication context into relayed HTTP requests.
 *
 * Implementations of this interface define how an authenticated user should be
 * attached to a relayed request. For example, this may involve setting a bearer token,
 * attaching a session cookie, or adding custom authentication headers.
 */
interface SpecialAuthenticationInjectorContract
{
    public function attach(
        PendingRequest $pendingRequest,
        Authenticatable $authenticatable
    ): PendingRequest;
}
