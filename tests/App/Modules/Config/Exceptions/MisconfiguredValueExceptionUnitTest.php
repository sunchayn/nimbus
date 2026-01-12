<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Config\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Contracts\SpecialAuthenticationInjectorContract;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(MisconfiguredValueException::class)]
class MisconfiguredValueExceptionUnitTest extends TestCase
{
    public function test_because_special_authentication_injector_is_invalid(): void
    {
        // Act

        $exception = MisconfiguredValueException::becauseSpecialAuthenticationInjectorIsInvalid();

        // Assert

        $this->assertEquals(
            'The config value for `nimbus.auth.special.injector` MUST be a class string of type <'.SpecialAuthenticationInjectorContract::class.'>',
            $exception->getMessage(),
        );

        $this->assertEquals(MisconfiguredValueException::SPECIAL_AUTHENTICATION_INJECTOR, $exception->getCode());
    }

    public function test_because_of_missing_dependency(): void
    {
        // Arrange

        $dependency = 'some/package';

        // Act

        $exception = MisconfiguredValueException::becauseOfMissingDependency($dependency);

        // Assert

        $this->assertEquals(
            'The config value for `nimbus.auth.special.injector` is an injector that requires the following dependency <some/package>',
            $exception->getMessage(),
        );

        $this->assertEquals(MisconfiguredValueException::MISSING_DEPENDENCIES, $exception->getCode());
    }

    public function test_because_of_invalid_guard_injector_combination(): void
    {
        // Arrange

        $suggestion = 'Try using another guard.';

        // Act

        $exception = MisconfiguredValueException::becauseOfInvalidGuardInjectorCombination($suggestion);

        // Assert

        $this->assertEquals(
            "The config value for `nimbus.auth.guard` doesn't work with the selected injector. Try using another guard.",
            $exception->getMessage(),
        );

        $this->assertEquals(MisconfiguredValueException::INVALID_GUARD_INJECTOR_COMBINATION, $exception->getCode());
    }

    public function test_because_default_application_is_invalid(): void
    {
        // Arrange

        $key = 'missing-app';

        // Act

        $exception = MisconfiguredValueException::becauseDefaultApplicationIsInvalid($key);

        // Assert

        $this->assertEquals(
            "The default application `missing-app` doesn't have a matching configuration.",
            $exception->getMessage(),
        );

        $this->assertEquals(MisconfiguredValueException::INVALID_DEFAULT_APPLICATION, $exception->getCode());
    }

    public function test_because_applications_are_not_defined(): void
    {
        // Act

        $exception = MisconfiguredValueException::becauseApplicationsAreNotDefined();

        // Assert

        $this->assertEquals(
            'There are no applications defined.',
            $exception->getMessage(),
        );

        $this->assertEquals(MisconfiguredValueException::INVALID_APPLICATIONS, $exception->getCode());
    }
}
