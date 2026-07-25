<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Handlers;

use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;

/**
 * Creates authorization handlers based on credential types.
 *
 * Uses factory pattern to instantiate appropriate handlers for different
 * authorization methods (Bearer, Basic, Current User, etc.) with proper validation.
 *
 * @example
 * Input:  AuthorizationCredentials(type: 'bearer', value: 'token123')
 * Output: BearerAuthorizationHandler instance
 */
class AuthorizationHandlerFactory
{
    public function create(AuthorizationCredentials $authorizationCredentials): AuthorizationHandler
    {
        return match ($authorizationCredentials->type) {
            AuthorizationTypeEnum::CurrentUser => $this->buildCurrentUserHandler(),
            AuthorizationTypeEnum::Impersonate => $this->buildImpersonateHandler($authorizationCredentials->value),
            AuthorizationTypeEnum::Bearer => $this->buildBearerHandler($authorizationCredentials->value),
            AuthorizationTypeEnum::Basic => $this->buildBasicHandler($authorizationCredentials->value),
            AuthorizationTypeEnum::None => $this->buildNoAuthorizationHandler(),
        };
    }

    protected function buildCurrentUserHandler(): CurrentUserAuthorizationHandler
    {
        // Current user authorization requires access to the application's, so we use the IoT for that.
        return resolve(CurrentUserAuthorizationHandler::class);
    }

    /**
     * @param  string|array{username: string, password: string}|null  $rawValue
     */
    protected function buildImpersonateHandler(string|array|null $rawValue): ImpersonateUserAuthorizationHandler
    {
        if ($rawValue === null) {
            throw new \InvalidArgumentException('Impersonate user ID cannot be null.');
        }

        if (! ctype_digit($rawValue)) {
            throw new \InvalidArgumentException('Impersonate user ID must be an integer.');
        }

        return resolve(ImpersonateUserAuthorizationHandler::class, ['userId' => (int) $rawValue]);
    }

    /**
     * @param  string|array{username: string, password: string}|null  $rawValue
     */
    protected function buildBearerHandler(string|array|null $rawValue): BearerAuthorizationHandler
    {
        if (! is_string($rawValue)) {
            throw new \InvalidArgumentException('Bearer token must be a string');
        }

        return resolve(BearerAuthorizationHandler::class, ['token' => $rawValue]);
    }

    /**
     * @param  string|array{username: string, password: string}|null  $rawValue
     */
    protected function buildBasicHandler(string|array|null $rawValue): BasicAuthAuthorizationHandler
    {
        if (! is_array($rawValue)) {
            throw new \InvalidArgumentException('Basic auth credentials must be an array');
        }

        return BasicAuthAuthorizationHandler::fromArray($rawValue);
    }

    protected function buildNoAuthorizationHandler(): NoAuthorizationHandler
    {
        return resolve(NoAuthorizationHandler::class);
    }
}
