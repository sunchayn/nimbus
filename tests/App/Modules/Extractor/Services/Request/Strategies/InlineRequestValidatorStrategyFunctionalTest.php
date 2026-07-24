<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies;

use Generator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetConcreteValueFromAstExprAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\InlineRequestValidatorStrategy;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InlineRequestValidatorStrategy::class)]
class InlineRequestValidatorStrategyFunctionalTest extends TestCase
{
    private SchemaBuilder|MockInterface $schemaBuilderMock;

    private InlineRequestValidatorStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaBuilderMock = $this->mock(SchemaBuilder::class);

        $this->strategy = new InlineRequestValidatorStrategy(
            schemaBuilder: $this->schemaBuilderMock,
            getConcreteValueFromAstExprAction: new GetConcreteValueFromAstExprAction,
        );
    }

    #[DataProvider('invalidRouteProvider')]
    public function test_it_returns_null_when_route_is_invalid(?string $controllerClass, ?string $methodName): void
    {
        // Arrange

        $route = Mockery::mock(Route::class);

        // Anticipate

        $route->shouldReceive('getControllerClass')->andReturn($controllerClass);

        $route->shouldReceive('getActionMethod')->andReturn($methodName);

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertNull($schema);
    }

    public static function invalidRouteProvider(): Generator
    {
        yield 'missing controller class' => [
            'controllerClass' => null,
            'methodName' => 'index',
        ];

        yield 'missing action method' => [
            'controllerClass' => 'DummyController',
            'methodName' => null,
        ];

        yield 'empty controller class' => [
            'controllerClass' => '',
            'methodName' => 'index',
        ];
    }

    public function test_it_extracts_inline_validation_schema(): void
    {
        // Arrange

        $route = Mockery::mock(Route::class);

        $expectedSchema = new Schema([new StringSchemaProperty('title')]);

        // Anticipate

        $route->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);

        $route->shouldReceive('getActionMethod')->andReturn('withInlineValidation');

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->with(Mockery::type(Ruleset::class))
            ->andReturn($expectedSchema);

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame($expectedSchema, $schema);
    }

    public function test_it_extracts_inline_validation_with_bag_and_method_call_rules(): void
    {
        // Anticipate

        $routeOneMock = Mockery::mock(Route::class);
        $routeOneMock->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeOneMock->shouldReceive('getActionMethod')->andReturn('withValidateWithBag');

        $routeTwoMock = Mockery::mock(Route::class);
        $routeTwoMock->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeTwoMock->shouldReceive('getActionMethod')->andReturn('withMethodRules');

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->twice()
            ->with(Mockery::type(Ruleset::class))
            ->andReturn(
                $schemaBuilderResultStub = new Schema([
                    new StringSchemaProperty('name'),
                ]),
            );

        // Act & Assert

        $schemaBag = $this->strategy->attempt($routeOneMock);
        $this->assertSame($schemaBuilderResultStub, $schemaBag);

        $schemaMethod = $this->strategy->attempt($routeTwoMock);
        $this->assertSame($schemaBuilderResultStub, $schemaMethod);
    }

    public function test_it_returns_empty_or_null_when_method_is_missing_or_has_no_rules(): void
    {
        // Anticipate

        $routeMissingMethod = Mockery::mock(Route::class);
        $routeMissingMethod->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeMissingMethod->shouldReceive('getActionMethod')->andReturn('nonExistentMethod');

        $routeNoValidation = Mockery::mock(Route::class);
        $routeNoValidation->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeNoValidation->shouldReceive('getActionMethod')->andReturn('withNoValidation');

        $routeEmptyValidate = Mockery::mock(Route::class);
        $routeEmptyValidate->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeEmptyValidate->shouldReceive('getActionMethod')->andReturn('withEmptyValidate');

        // Act & Assert

        $schemaMissing = $this->strategy->attempt($routeMissingMethod);
        $this->assertTrue($schemaMissing->isEmpty());

        $schemaNoVal = $this->strategy->attempt($routeNoValidation);
        $this->assertNull($schemaNoVal);

        $schemaEmptyVal = $this->strategy->attempt($routeEmptyValidate);
        $this->assertNull($schemaEmptyVal);
    }
}

class InlineValidationControllerStub
{
    public function withInlineValidation(Request $request): void
    {
        $request->validate(['title' => 'required|string']);
    }

    public function withValidateWithBag(Request $request): void
    {
        $request->validateWithBag('myBag', ['name' => 'required|string']);
    }

    public function withMethodRules(Request $request): void
    {
        $request->validate($this->rules());
    }

    public function rules(): array
    {
        return ['name' => 'required|string'];
    }

    public function withNoValidation(Request $request): void {}

    public function withEmptyValidate(Request $request): void
    {
        $request->validate();
    }
}
