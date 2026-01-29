<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\InvalidRouteDefinitionException;

#[CoversClass(InvalidRouteDefinitionException::class)]
class InvalidRouteDefinitionExceptionUnitTest extends TestCase
{
    public function test_it_creates_exception_for_missing_controller_method(): void
    {
        // Arrange

        $routeUri = '/api/test';
        $routeMethods = ['GET'];
        $controllerClass = 'App\Http\Controllers\TestController';
        $controllerMethod = 'missingMethod';

        // Act

        $exception = InvalidRouteDefinitionException::forRoute(
            $routeUri,
            $routeMethods,
            $controllerClass,
            $controllerMethod
        );

        // Assert

        $this->assertInstanceOf(InvalidRouteDefinitionException::class, $exception);
        $this->assertStringContainsString(
            "Controller method '$controllerMethod' not found in class '$controllerClass' for route '$routeUri'",
            $exception->getMessage()
        );

        $this->assertStringContainsString(
            "Check that the method '$controllerMethod' exists in the '$controllerClass' class",
            $exception->getSuggestedSolution()
        );
    }

    public function test_it_creates_exception_for_malformed_uses_statement(): void
    {
        // Arrange

        $routeUri = '/api/test';
        $routeMethods = ['POST'];
        $controllerClass = ''; // Empty class implies malformed or closure based but treated here as missing
        $controllerMethod = 'someMethod';

        // Act

        // The logic in InvalidRouteDefinitionException checks for filled($controllerClass) && filled($controllerMethod)
        // If one is missing, it assumes malformed uses statement logic.
        $exception = InvalidRouteDefinitionException::forRoute(
            $routeUri,
            $routeMethods,
            $controllerClass,
            $controllerMethod
        );

        // Assert

        $this->assertInstanceOf(InvalidRouteDefinitionException::class, $exception);
        $this->assertStringContainsString(
            "Malformed `uses` statement for route '$routeUri'",
            $exception->getMessage()
        );

        $this->assertStringContainsString(
            'Make sure the `uses` statement is properly formatted',
            $exception->getSuggestedSolution()
        );
    }

    public function test_it_provides_correct_route_context(): void
    {
        // Arrange

        $routeUri = '/users';
        $routeMethods = ['GET', 'HEAD'];
        $controllerClass = 'UserController';
        $controllerMethod = 'index';

        // Act

        $exception = InvalidRouteDefinitionException::forRoute(
            $routeUri,
            $routeMethods,
            $controllerClass,
            $controllerMethod
        );

        $context = $exception->getRouteContext();

        // Assert

        $this->assertSame($routeUri, $context['uri']);
        $this->assertSame($routeMethods, $context['methods']);
        $this->assertSame($controllerClass, $context['controllerClass']);
        $this->assertSame($controllerMethod, $context['controllerMethod']);
    }
}
