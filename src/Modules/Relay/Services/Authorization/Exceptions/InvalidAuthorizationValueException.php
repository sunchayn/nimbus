<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Exceptions;

use RuntimeException;

class InvalidAuthorizationValueException extends RuntimeException
{
    public const BEARER_TOKEN_IS_NOT_STRING = 1;

    public const BASIC_AUTH_SHAPE_IS_INVALID = 2;

    public const USER_IS_NOT_FOUND = 3;

    public const REMEMBER_ME_COOKIE_IS_CORRUPT = 4;

    public const TYPE_IS_CURRENT_HOST_ONLY = 5;

    public const USER_CANNOT_HAVE_TOKENS = 6;

    public static function becauseUserCannotHaveTokens(): self
    {
        return new self(
            message: 'The user model cannot create tokens. Use the `HasApiTokens` trait of Sanctum or Passport.',
            code: self::USER_CANNOT_HAVE_TOKENS,
        );
    }

    public static function becauseTypeIsCurrentHostOnly(): self
    {
        return new self(
            message: 'This authorization type is only available for the current host.',
            code: self::TYPE_IS_CURRENT_HOST_ONLY,
        );
    }

    public static function becauseBearerTokenValueIsNotString(): self
    {
        return new self(
            message: 'Bearer token value is not a string.',
            code: self::BEARER_TOKEN_IS_NOT_STRING,
        );
    }

    public static function becauseBasicAuthCredentialsAreInvalid(): self
    {
        return new self(
            message: 'Basic Auth credentials are invalid. Expects array{username: string, password: string}.',
            code: self::BASIC_AUTH_SHAPE_IS_INVALID,
        );
    }

    public static function becauseUserIsNotFound(): self
    {
        return new self(
            message: "User ID didn't resolve to a user to impersonate.",
            code: self::USER_IS_NOT_FOUND,
        );
    }

    public static function becauseCookieIsNotDecryptable(): self
    {
        return new self(
            message: 'The Current User remember me cookie is corrupt. Reload the page, or pick a different authorization method.',
            code: self::REMEMBER_ME_COOKIE_IS_CORRUPT,
        );
    }
}
