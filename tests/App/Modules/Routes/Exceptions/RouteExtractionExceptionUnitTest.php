<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;

#[CoversClass(RouteExtractionException::class)]
class RouteExtractionExceptionUnitTest extends TestCase
{
    public function test_it_handles_missing_route_context_gracefully(): void
    {
        // Arrange

        $exception = new class('Test message') extends RouteExtractionException
        {
            // Concrete implementation
        };

        // Act

        $context = $exception->getRouteContext();
        $ignoreData = $exception->getIgnoreData();

        // Assert

        $this->assertNull($context['uri']);
        $this->assertNull($context['methods']);
        $this->assertSame('[unspecified]', $context['controllerClass']);
        $this->assertSame('[unspecified]', $context['controllerMethod']);

        $this->assertNull($ignoreData);
    }

    public function test_it_generates_ignore_data_when_route_info_is_present(): void
    {
        // Arrange

        $routeUri = '/api/users';
        $routeMethods = ['GET', 'POST'];

        $exception = new class('Test', $routeUri, $routeMethods) extends RouteExtractionException
        {
            // Concrete implementation
        };

        // Act

        $ignoreData = $exception->getIgnoreData();

        // Assert

        $this->assertNotNull($ignoreData);
        $this->assertStringContainsString($routeUri, $ignoreData);
        $this->assertStringContainsString('GET', $ignoreData);
        $this->assertStringContainsString('POST', $ignoreData);
    }

    public function test_it_returns_correct_frontend_identifier(): void
    {
        // Arrange

        $exception = new class('Test') extends RouteExtractionException {};

        // Act

        $identifier = $exception->getFrontEndIdentifier();

        // Assert

        $this->assertSame('routeExtractorException', $identifier);
    }

    public function test_to_array_structure(): void
    {
        // Arrange

        $exception = new class(message: 'Test message', routeUri: '/test', routeMethods: ['GET'], controllerClass: 'TestController', controllerMethod: 'index', suggestedSolution: 'Fix it') extends RouteExtractionException {};

        // Act

        $array = $exception->toArray();

        // Assert

        $this->assertIsArray($array);
        $this->assertSame('Test message', $array['exception']['message']);
        $this->assertSame('/test', $array['routeContext']['uri']);
        $this->assertSame(['GET'], $array['routeContext']['methods']);
        $this->assertSame('TestController', $array['routeContext']['controllerClass']);
        $this->assertSame('Fix it', $array['suggestedSolution']);
        $this->assertNotNull($array['ignoreData']);
    }
}
