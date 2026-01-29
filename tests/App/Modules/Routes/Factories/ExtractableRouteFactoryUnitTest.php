<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Factories;

use Closure;
use Generator;
use Illuminate\Routing\Route;
use Mockery;
use PhpParser\Parser\Php8;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionParameter;
use RuntimeException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\InvalidRouteDefinitionException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;
use Sunchayn\Nimbus\Modules\Routes\Factories\ExtractableRouteFactory;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Factories\Stubs\ExtractableControllerStub;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Factories\Stubs\RequestStub;

#[CoversClass(ExtractableRouteFactory::class)]
#[CoversClass(InvalidRouteDefinitionException::class)]
#[CoversClass(RouteExtractionException::class)]
class ExtractableRouteFactoryUnitTest extends TestCase
{
    private ExtractableRouteFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ExtractableRouteFactory;
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_creates_extractable_route_from_valid_controller_route(): void
    {
        // Arrange

        $route = new Route(
            ['GET'],
            '/users',
            ['uses' => ExtractableControllerStub::class.'@index'],
        );

        // Act

        $extractableRoute = $this->factory->fromLaravelRoute($route);

        // Assert

        $this->assertEquals('index', $extractableRoute->methodName);

        $this->assertEquals(
            ExtractableControllerStub::class,
            $extractableRoute->controllerClass,
        );

        $this->assertEquals('index', $extractableRoute->controllerMethod);

        $this->assertEquals([], $extractableRoute->parameters);

        $this->assertParsedCodeEquals(
            __DIR__.'/Stubs/ExtractableControllerStub.php',
            $extractableRoute->codeParser,
        );
    }

    public function test_it_extracts_method_parameters_correctly(): void
    {
        // Arrange

        $route = new Route(
            ['POST'],
            '/users',
            action: ['uses' => ExtractableControllerStub::class.'@store'] // <- this method has parameters
        );

        // Act

        $extractableRoute = $this->factory->fromLaravelRoute($route);

        // Assert

        $this->assertCount(1, $extractableRoute->parameters);

        $this->assertInstanceOf(ReflectionParameter::class, $extractableRoute->parameters[0]);

        $this->assertSame('request', $extractableRoute->parameters[0]->getName());

        $this->assertSame(RequestStub::class, $extractableRoute->parameters[0]->getType()->getName());
    }

    public function test_it_returns_empty_route_for_closure_based_routes(): void
    {
        // Arrange

        $route = new Route(
            ['POST'],
            '/closure',
            action: ['uses' => fn () => 'response'],
        );

        // Act

        $extractableRoute = $this->factory->fromLaravelRoute($route);

        // Assert

        $this->assertEmpty($extractableRoute->parameters);

        $this->assertEmpty(($extractableRoute->codeParser)());

        $this->assertNull($extractableRoute->methodName);

        $this->assertNull($extractableRoute->controllerClass);

        $this->assertNull($extractableRoute->controllerMethod);
    }

    #[DataProvider('invalidRoutesDataProvider')]
    public function test_it_breaks_correctly_for_invalid_routes(
        Route $route,
        string $expectedException,
        string $expectedExceptionMessage,
        string $expectedControllerClass,
        string $expectedControllerMethod,
        string $expectedSuggestedSolution,
        string $expectedIgnoreData,
    ): void {
        // Act

        try {
            $this->factory->fromLaravelRoute($route);
        } catch (RouteExtractionException $actualException) {
        }

        // Assert

        $this->assertInstanceOf($expectedException, $actualException);

        $this->assertEquals(
            $expectedExceptionMessage,
            $actualException->getMessage(),
        );

        $this->assertEquals(
            $expectedSuggestedSolution,
            $actualException->getSuggestedSolution(),
        );

        $this->assertEquals(
            $expectedIgnoreData,
            $actualException->getIgnoreData(),
        );

        $this->assertEquals(
            [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'controllerClass' => $expectedControllerClass,
                'controllerMethod' => $expectedControllerMethod,
            ],
            $actualException->getRouteContext(),
        );

    }

    public static function invalidRoutesDataProvider(): Generator
    {
        yield 'controller class is empty' => [
            'route' => new Route(methods: ['GET'], uri: '/invalid', action: ['uses' => '@method']),
            'expectedException' => InvalidRouteDefinitionException::class,
            'expectedExceptionMessage' => "Malformed `uses` statement for route 'invalid'.",
            'expectedControllerClass' => '[unspecified]',
            'expectedControllerMethod' => 'method',
            'expectedSuggestedSolution' => 'Make sure the `uses` statement is properly formatted `{controllerClass}@{controllerMethod}`. If it is an invokable controller then it must not have the `@` suffix.',
            'expectedIgnoreData' => 'invalid|["GET","HEAD"]',
        ];

        yield 'controller method is empty' => [
            'route' => new Route(methods: ['GET'], uri: '/invalid-2', action: ['uses' => 'SomeController@']),
            'expectedException' => InvalidRouteDefinitionException::class,
            'expectedExceptionMessage' => "Malformed `uses` statement for route 'invalid-2'.",
            'expectedControllerClass' => 'SomeController',
            'expectedControllerMethod' => '[unspecified]',
            'expectedSuggestedSolution' => 'Make sure the `uses` statement is properly formatted `{controllerClass}@{controllerMethod}`. If it is an invokable controller then it must not have the `@` suffix.',
            'expectedIgnoreData' => 'invalid-2|["GET","HEAD"]',
        ];

        yield 'controller class doesnt exist' => [
            'route' => new Route(methods: ['GET'], uri: '/invalid-3', action: ['uses' => 'App\Http\Controllers\NonExistentController@index']),
            'expectedException' => InvalidRouteDefinitionException::class,
            'expectedExceptionMessage' => "Controller method 'index' not found in class 'App\Http\Controllers\NonExistentController' for route 'invalid-3'.",
            'expectedControllerClass' => 'App\Http\Controllers\NonExistentController',
            'expectedControllerMethod' => 'index',
            'expectedSuggestedSolution' => "Check that the method 'index' exists in the 'App\Http\Controllers\NonExistentController' class. This usually indicates an incorrect route definition in your routes file.",
            'expectedIgnoreData' => 'invalid-3|["GET","HEAD"]',
        ];

        yield 'controller method doesnt exist' => [
            'route' => new Route(methods: ['GET'], uri: '/invalid-4', action: ['uses' => ExtractableControllerStub::class.'@nonExistentMethod']),
            'expectedException' => InvalidRouteDefinitionException::class,
            'expectedExceptionMessage' => sprintf("Controller method 'nonExistentMethod' not found in class '%s' for route 'invalid-4'.", ExtractableControllerStub::class),
            'expectedControllerClass' => ExtractableControllerStub::class,
            'expectedControllerMethod' => 'nonExistentMethod',
            'expectedSuggestedSolution' => sprintf("Check that the method 'nonExistentMethod' exists in the '%s' class. This usually indicates an incorrect route definition in your routes file.", ExtractableControllerStub::class),
            'expectedIgnoreData' => 'invalid-4|["GET","HEAD"]',
        ];
    }

    #[RunInSeparateProcess] // <- Having overload Mock here, let's not leak it.
    #[PreserveGlobalState(false)]
    public function test_it_handles_file_read_errors_gracefully(): void
    {
        // Arrange

        $route = new Route(
            ['GET'],
            '/users',
            action: ['uses' => ExtractableControllerStub::class.'@store']
        );

        $extractableRoute = $this->factory->fromLaravelRoute($route);

        $parserFactoryMock = Mockery::mock('overload:'.ParserFactory::class);
        $parserMock = Mockery::mock('overload:'.Php8::class);

        // Anticipate

        $parserMock->shouldReceive('parse')->andThrow(new RuntimeException('Cannot parse.'));
        $parserFactoryMock->shouldReceive('createForNewestSupportedVersion')->andReturn($parserMock);

        // Act

        $result = ($extractableRoute->codeParser)();

        // Assert

        $this->assertNull($result);
    }

    /*
     * Asserts.
     */

    private function assertParsedCodeEquals(string $expected, Closure $parser): void
    {
        $actualParsedCode = $parser();

        $expectedParsedCode = file_get_contents($expected);
        $this->assertEquals(
            (new ParserFactory)->createForNewestSupportedVersion()->parse($expectedParsedCode),
            $actualParsedCode,
        );
    }
}
