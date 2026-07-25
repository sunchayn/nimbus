<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions\InvalidAuthorizationValueException;

class BasicAuthAuthorizationHandler implements AuthorizationHandler
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {
        if (in_array(trim($username), ['', '0'], true) || in_array(trim($password), ['', '0'], true)) {
            throw InvalidAuthorizationValueException::becauseBasicAuthCredentialsAreInvalid();
        }
    }

    /**
     * @param  array{username?: string, password?: string}  $credentials
     */
    public static function fromArray(array $credentials): self
    {
        if (! array_key_exists('username', $credentials) || ! array_key_exists('password', $credentials)) {
            throw InvalidAuthorizationValueException::becauseBasicAuthCredentialsAreInvalid();
        }

        return new self($credentials['username'], $credentials['password']);
    }

    public function authorize(PendingRequest $pendingRequest): PendingRequest
    {
        return $pendingRequest->withBasicAuth($this->username, $this->password);
    }
}
