<?php

namespace Sunchayn\Nimbus\Modules\Config\Exceptions;

use Exception;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts\SpecialAuthenticationInjectorContract;

class MisconfiguredValueException extends Exception
{
    public const SPECIAL_AUTHENTICATION_INJECTOR = 1;

    public const MISSING_DEPENDENCIES = 2;

    public const INVALID_GUARD_INJECTOR_COMBINATION = 3;

    public const INVALID_DEFAULT_APPLICATION = 4;

    public const INVALID_APPLICATIONS = 5;

    public const INVALID_ROUTE_PROCESSING_STRATEGY = 6;

    public static function becauseRouteProcessingStrategyIsInvalid(mixed $strategy): self
    {
        $value = is_scalar($strategy) ? (string) $strategy : gettype($strategy);

        return new self(
            message: sprintf('The configured route processing strategy `%s` is invalid.', $value),
            code: self::INVALID_ROUTE_PROCESSING_STRATEGY,
        );
    }

    public static function becauseSpecialAuthenticationInjectorIsInvalid(): self
    {
        return new self(
            message: 'The config value for `nimbus.auth.special.injector` MUST be a class string of type <'.SpecialAuthenticationInjectorContract::class.'>',
            code: self::SPECIAL_AUTHENTICATION_INJECTOR,
        );
    }

    public static function becauseOfMissingDependency(string $dependency): self
    {
        return new self(
            message: sprintf('The config value for `nimbus.auth.special.injector` is an injector that requires the following dependency <%s>', $dependency),
            code: self::MISSING_DEPENDENCIES,
        );
    }

    /**
     * @param  non-empty-string  $suggestion
     */
    public static function becauseOfInvalidGuardInjectorCombination(string $suggestion): self
    {
        return new self(
            message: "The config value for `nimbus.auth.guard` doesn't work with the selected injector. ".$suggestion,
            code: self::INVALID_GUARD_INJECTOR_COMBINATION,
        );
    }

    public static function becauseDefaultApplicationIsInvalid(string $key): self
    {
        return new self(
            message: sprintf("The default application `%s` doesn't have a matching configuration.", $key),
            code: self::INVALID_DEFAULT_APPLICATION,
        );
    }

    public static function becauseApplicationsAreNotDefined(): self
    {
        return new self(
            message: 'There are no applications defined.',
            code: self::INVALID_APPLICATIONS,
        );
    }
}
