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
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\SchemaBuilder;
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

    #[DataProvider('staticAndSideEffectScenariosProvider')]
    public function test_it_extracts_static_validation_rules_and_avoids_side_effects(
        string $methodName,
        bool $expectRules,
    ): void {
        // Arrange

        InlineValidationSideEffectStub::$called = false;

        // Anticipate

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeMock->shouldReceive('getActionMethod')->andReturn($methodName);

        if ($expectRules) {
            $this->schemaBuilderMock
                ->shouldReceive('buildSchemaFromRuleset')
                ->once()
                ->with(Mockery::type(Ruleset::class))
                ->andReturn(new Schema([new StringSchemaProperty('email')]));
        }

        // Act

        $schema = $this->strategy->attempt($routeMock);

        // Assert

        $this->assertFalse(
            InlineValidationSideEffectStub::$called,
            'Route extraction must not execute static or instantiation side-effects in variable assignments.'
        );

        if ($expectRules) {
            $this->assertNotNull($schema);
        } else {
            $this->assertNull($schema);
        }
    }

    public static function staticAndSideEffectScenariosProvider(): Generator
    {
        yield 'self static validation rules' => [
            'methodName' => 'withSelfStaticRules',
            'expectRules' => true,
        ];

        yield 'static keyword validation rules' => [
            'methodName' => 'withStaticKeywordRules',
            'expectRules' => true,
        ];

        yield 'class name static validation rules' => [
            'methodName' => 'withClassNameStaticRules',
            'expectRules' => true,
        ];

        yield 'static call side effect assignment' => [
            'methodName' => 'withStaticSideEffectAssignment',
            'expectRules' => true,
        ];

        yield 'new instance side effect assignment' => [
            'methodName' => 'withNewSideEffectAssignment',
            'expectRules' => true,
        ];

        yield 'foreign class static call yields no rules' => [
            'methodName' => 'withForeignClassStaticRules',
            'expectRules' => false,
        ];

        yield 'dynamic static call yields no rules' => [
            'methodName' => 'withDynamicStaticCall',
            'expectRules' => false,
        ];

        yield 'dynamic class static call yields no rules' => [
            'methodName' => 'withDynamicClassStaticCall',
            'expectRules' => false,
        ];
    }

    public function test_it_extracts_validation_rules_from_validator_make_facade_call(): void
    {
        // Arrange

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeMock->shouldReceive('getActionMethod')->andReturn('withValidatorMakeFacade');

        $expectedSchema = new Schema([
            new StringSchemaProperty('current_password'),
            new StringSchemaProperty('password'),
        ]);

        // Anticipate

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->withArgs(function (Ruleset $ruleset): bool {
                $this->assertEquals([
                    'current_password' => ['required', 'string', 'current_password:web'],
                    'password' => ['required', 'string', 'min:8'],
                ], $ruleset->toArray());

                return true;
            })
            ->andReturn($expectedSchema);

        // Act

        $schema = $this->strategy->attempt($routeMock);

        // Assert

        $this->assertSame($expectedSchema, $schema);
    }

    public function test_it_extracts_validation_rules_from_validator_helper_function_call(): void
    {
        // Arrange

        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getControllerClass')->andReturn(InlineValidationControllerStub::class);
        $routeMock->shouldReceive('getActionMethod')->andReturn('withValidatorHelper');

        $expectedSchema = new Schema([
            new StringSchemaProperty('email'),
        ]);

        // Anticipate

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->withArgs(function (Ruleset $ruleset): bool {
                $this->assertEquals([
                    'email' => ['required', 'email'],
                ], $ruleset->toArray());

                return true;
            })
            ->andReturn($expectedSchema);

        // Act

        $schema = $this->strategy->attempt($routeMock);

        // Assert

        $this->assertSame($expectedSchema, $schema);
    }
}

class InlineValidationSideEffectStub
{
    public static bool $called = false;

    public static function trigger(): string
    {
        self::$called = true;

        return 'side_effect_triggered';
    }
}

class InlineValidationControllerStub
{
    public function withValidatorMakeFacade(Request $request): void
    {
        \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => 'Custom message',
        ])->validateWithBag('updatePassword');
    }

    public function withValidatorHelper(Request $request): void
    {
        validator($request->all(), [
            'email' => 'required|email',
        ]);
    }

    public function passwordRules(): array
    {
        return ['required', 'string', 'min:8'];
    }

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

    public function withSelfStaticRules(Request $request): void
    {
        $request->validate(self::staticRules());
    }

    public function withStaticKeywordRules(Request $request): void
    {
        $request->validate(static::staticRules());
    }

    public function withClassNameStaticRules(Request $request): void
    {
        $request->validate(InlineValidationControllerStub::staticRules());
    }

    public function withStaticSideEffectAssignment(Request $request): void
    {
        $effect = InlineValidationSideEffectStub::trigger();
        $request->validate(self::staticRules());
    }

    public function withNewSideEffectAssignment(Request $request): void
    {
        $instance = new InlineValidationSideEffectStub;
        $request->validate(self::staticRules());
    }

    public function withForeignClassStaticRules(Request $request): void
    {
        $request->validate(\stdClass::getValidationRules());
    }

    public function withDynamicStaticCall(Request $request): void
    {
        $method = 'staticRules';
        $request->validate(self::{$method}());
    }

    public function withDynamicClassStaticCall(Request $request): void
    {
        $class = 'self';
        $request->validate($class::staticRules());
    }

    public static function staticRules(): array
    {
        return ['email' => 'required|email'];
    }

    public function withNoValidation(Request $request): void {}

    public function withEmptyValidate(Request $request): void
    {
        $request->validate();
    }
}
