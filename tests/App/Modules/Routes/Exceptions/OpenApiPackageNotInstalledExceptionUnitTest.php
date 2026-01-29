<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\OpenApiPackageNotInstalledException;

#[CoversClass(OpenApiPackageNotInstalledException::class)]
class OpenApiPackageNotInstalledExceptionUnitTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        // Arrange

        $exception = new OpenApiPackageNotInstalledException;

        // Act

        $message = $exception->getMessage();

        // Assert

        $this->assertSame(
            'The OpenAPI route extraction strategy requires the "devizzent/cebe-php-openapi" package to be installed.',
            $message
        );
    }

    public function test_it_returns_correct_frontend_identifier(): void
    {
        // Arrange

        $exception = new OpenApiPackageNotInstalledException;

        // Act

        $identifier = $exception->getFrontEndIdentifier();

        // Assert

        $this->assertSame('globalException', $identifier);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        // Arrange

        $previousException = new Exception('Previous error', 123);
        $exception = new OpenApiPackageNotInstalledException($previousException);

        // Act

        $array = $exception->toArray();

        // Assert

        $this->assertIsArray($array);
        $this->assertArrayHasKey('exception', $array);
        $this->assertArrayHasKey('suggestedSolution', $array);

        $this->assertSame(
            'Install the package by running: composer require devizzent/cebe-php-openapi',
            $array['suggestedSolution']
        );

        $this->assertSame($exception->getMessage(), $array['exception']['message']);
        $this->assertSame('Previous error', $array['exception']['previous']['message']);
        $this->assertSame($previousException->getFile(), $array['exception']['previous']['file']);
        $this->assertSame($previousException->getLine(), $array['exception']['previous']['line']);
    }

    public function test_to_array_handles_null_previous_exception(): void
    {
        // Arrange

        $exception = new OpenApiPackageNotInstalledException;

        // Act

        $array = $exception->toArray();

        // Assert

        $this->assertNull($array['exception']['previous']);
    }
}
