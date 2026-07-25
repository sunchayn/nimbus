<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;

class BearerAuthorizationHandler implements AuthorizationHandler
{
    public function __construct(
        public readonly string $token,
    ) {
        if (in_array(trim($token), ['', '0'], true)) {
            throw InvalidAuthorizationValueException::becauseBearerTokenValueIsNotString();
        }
    }

    public function authorize(PendingRequest $pendingRequest): PendingRequest
    {
        return $pendingRequest->withToken($this->token);
    }
}
