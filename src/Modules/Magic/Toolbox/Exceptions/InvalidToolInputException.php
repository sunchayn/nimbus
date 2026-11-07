<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions;

use Exception;

class InvalidToolInputException extends Exception
{
    public static function becauseValueCannotBeDecrypted(): self
    {
        return new self('The value cannot be decrypted with the app key.');
    }

    public static function becauseRouteIsUnknown(string $route): self
    {
        return new self(sprintf('The route `%s` does not exist.', $route));
    }

    public static function becauseUrlCannotBeGenerated(string $reason): self
    {
        return new self($reason);
    }

    public static function becauseTokenIsNotAJwt(): self
    {
        return new self('The value is not a valid JWT.');
    }

    public static function becauseSanctumIsMissing(): self
    {
        return new self('Laravel Sanctum is not installed.');
    }

    public static function becauseTokenIsUnknown(): self
    {
        return new self('No Sanctum token matches this value.');
    }

    public static function becauseSessionIsUnknown(): self
    {
        return new self('No stored session matches this value.');
    }
}
