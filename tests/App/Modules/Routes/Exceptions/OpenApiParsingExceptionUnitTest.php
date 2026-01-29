<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\OpenApiParsingException;

#[CoversClass(OpenApiParsingException::class)]
class OpenApiParsingExceptionUnitTest extends TestCase
{
    public function test_it_can_be_instantiated_with_message_and_previous_exception(): void
    {
        // Arrange

        $filePath = '/path/to/openapi.yaml';
        $parsingError = 'Syntax error at line 10';
        $previous = new Exception('Underlying YAML error');

        // Act

        $exception = new OpenApiParsingException($filePath, $parsingError, $previous);

        // Assert

        $this->assertInstanceOf(OpenApiParsingException::class, $exception);
        $this->assertSame(
            "Failed to parse OpenAPI specification file: $filePath",
            $exception->getMessage()
        );
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_it_returns_correct_frontend_identifier(): void
    {
        // Arrange

        $exception = new OpenApiParsingException('file', 'error');

        // Act

        $identifier = $exception->getFrontEndIdentifier();

        // Assert

        $this->assertSame('globalException', $identifier);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        // Arrange

        $filePath = 'openapi.json';
        $parsingError = 'Unexpected token';
        $exception = new OpenApiParsingException($filePath, $parsingError);

        // Act

        $array = $exception->toArray();

        // Assert

        $this->assertIsArray($array);
        $this->assertArrayHasKey('exception', $array);
        $this->assertArrayHasKey('suggestedSolution', $array);

        $this->assertSame(
            "Verify that the file exists and contains a valid OpenAPI 3.x specification. Error: $parsingError",
            $array['suggestedSolution']
        );

        $this->assertSame(
            "Failed to parse OpenAPI specification file: $filePath",
            $array['exception']['message']
        );
    }
}
