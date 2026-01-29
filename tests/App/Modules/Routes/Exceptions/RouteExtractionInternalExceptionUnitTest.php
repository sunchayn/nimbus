<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionInternalException;

#[CoversClass(RouteExtractionInternalException::class)]
class RouteExtractionInternalExceptionUnitTest extends TestCase
{
    public function test_it_creates_exception_correctly(): void
    {
        // Arrange

        $originalException = new Exception('Something went wrong', 500);
        $routeUri = '/api/resource';
        $routeMethods = ['GET'];
        $controllerClass = 'ResourceController';
        $controllerMethod = 'show';

        // Act

        $exception = RouteExtractionInternalException::forRoute(
            $originalException,
            $routeUri,
            $routeMethods,
            $controllerClass,
            $controllerMethod
        );

        // Assert

        $this->assertInstanceOf(RouteExtractionInternalException::class, $exception);
        $this->assertSame($originalException, $exception->getPrevious());
        $this->assertSame(500, $exception->getCode());

        $this->assertStringContainsString(
            "Failed to extract route information for '$routeUri' due to an unexpected error: Something went wrong",
            $exception->getMessage()
        );

        $this->assertStringContainsString(
            'Check the application logs for more details',
            $exception->getSuggestedSolution()
        );

        $this->assertStringContainsString(
            'https://github.com/sunchayn/nimbus/issues/new/choose',
            $exception->getSuggestedSolution()
        );
    }

    public function test_to_array_includes_correct_data(): void
    {
        // Arrange

        $originalException = new Exception('Crash', 123);
        $exception = RouteExtractionInternalException::forRoute(
            $originalException,
            '/home',
            ['GET']
        );

        // Act

        $array = $exception->toArray();

        // Assert

        $this->assertArrayHasKey('ignoreData', $array);
        // ignoreData is based on URI and methods, which are present
        $this->assertNotNull($array['ignoreData']);

        $this->assertArrayHasKey('routeContext', $array);
        $this->assertSame('/home', $array['routeContext']['uri']);
    }
}
