<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies;

use Generator;
use Illuminate\Routing\Route;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Ast\Actions\LoadClassAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\FormRequestStrategy;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs\FormRequestWithExceptionStub;
use Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs\StrategyControllerStub;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(FormRequestStrategy::class)]
class FormRequestStrategyFunctionalTest extends TestCase
{
    private SchemaBuilder|MockInterface $schemaBuilderMock;

    private LoadClassAstAction|MockInterface $loadClassAstActionMock;

    private FormRequestStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaBuilderMock = $this->mock(SchemaBuilder::class);
        $this->loadClassAstActionMock = $this->mock(LoadClassAstAction::class);

        $this->strategy = new FormRequestStrategy($this->schemaBuilderMock);
    }

    #[DataProvider('attemptMatchingProvider')]
    public function test_it_returns_null_when_no_form_request_found(
        string $controllerMethod,
        bool $expectsNull
    ): void {
        // Arrange

        $route = $this->makeRoute(StrategyControllerStub::class, $controllerMethod);

        // Anticipate

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->andReturn(new Schema([new StringSchemaProperty('name')]));

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame(
            $expectsNull,
            is_null($schema),
        );
    }

    public static function attemptMatchingProvider(): Generator
    {
        yield 'parameter request subclass triggers extraction' => [
            'controllerMethod' => 'withFormRequest',
            'expectsNull' => false,
        ];

        yield 'parameter request class returns null' => [
            'controllerMethod' => 'withBaseRequest',
            'expectsNull' => true,
        ];

        yield 'no parameters returns null' => [
            'controllerMethod' => 'withNoParams',
            'expectsNull' => true,
        ];

        yield 'parameter with no type hint returns null' => [
            'controllerMethod' => 'withUntypedParam',
            'expectsNull' => true,
        ];

        yield 'parameter with union type returns null' => [
            'controllerMethod' => 'withUnionParam',
            'expectsNull' => true,
        ];

        yield 'parameter with custom request lacking rules method returns null' => [
            'controllerMethod' => 'withRulesMissingParam',
            'expectsNull' => true,
        ];
    }

    public function test_it_extracts_rules_successfully(): void
    {
        // Arrange

        $route = $this->makeRoute(StrategyControllerStub::class, 'withFormRequest');

        $responseSchemaStub = new Schema([new StringSchemaProperty('name')]);

        // Anticipate

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->andReturn($responseSchemaStub);

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame($responseSchemaStub, $schema);

        $expectedRules = Ruleset::make([
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $this->schemaBuilderMock
            ->shouldHaveReceived('buildSchemaFromRuleset')
            ->once()
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $error) use ($expectedRules): bool {
                $this->assertEquals($expectedRules->all(), $ruleset->all());
                $this->assertNull($error);

                return true;
            });
    }

    public function test_it_handles_exception_when_calling_rules_method(): void
    {
        // Arrange

        $route = $this->makeRoute(StrategyControllerStub::class, 'withExceptionRequest');

        $responseSchemaStub = new Schema([new StringSchemaProperty('fallback')]);

        $parser = (new \PhpParser\ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse(<<<'PHP'
            <?php
            namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs;
            class FormRequestWithExceptionStub {
                public function rules(): array {
                    return ["name" => "required"];
                }
            }
            PHP);

        $traverser = new \PhpParser\NodeTraverser;
        $traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver);
        $stmts = $traverser->traverse($stmts);

        // Anticipate

        $this->loadClassAstActionMock
            ->shouldReceive('execute')
            ->with(FormRequestWithExceptionStub::class)
            ->once()
            ->andReturn($stmts);

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->andReturn($responseSchemaStub);

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame($responseSchemaStub, $schema);

        $this->schemaBuilderMock
            ->shouldHaveReceived('buildSchemaFromRuleset')
            ->once()
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $error): bool {
                $this->assertEquals(['name' => ['required']], $ruleset->all());
                $this->assertNotNull($error);

                return true;
            });
    }

    public function test_it_handles_exception_when_calling_rules_method_without_fallback_ast(): void
    {
        // Arrange

        $route = $this->makeRoute(StrategyControllerStub::class, 'withExceptionRequest');

        $responseSchemaStub = new Schema([new StringSchemaProperty('fallback')]);

        // Anticipate

        $this->loadClassAstActionMock
            ->shouldReceive('execute')
            ->with(FormRequestWithExceptionStub::class)
            ->once()
            ->andReturn([]);

        $this->schemaBuilderMock
            ->shouldReceive('buildSchemaFromRuleset')
            ->once()
            ->andReturn($responseSchemaStub);

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertSame($responseSchemaStub, $schema);

        $this->schemaBuilderMock
            ->shouldHaveReceived('buildSchemaFromRuleset')
            ->once()
            ->withArgs(function (Ruleset $ruleset, ?RulesExtractionError $error): bool {
                $this->assertTrue($ruleset->isEmpty());
                $this->assertNotNull($error);

                return true;
            });
    }

    public function test_it_returns_null_when_controller_class_does_not_exist(): void
    {
        // Arrange

        $route = $this->makeRoute('NonExistentController', 'someMethod');

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertNull($schema);
    }

    public function test_it_returns_null_when_controller_method_does_not_exist(): void
    {
        // Arrange

        $route = $this->makeRoute(StrategyControllerStub::class, 'nonExistentMethod');

        // Act

        $schema = $this->strategy->attempt($route);

        // Assert

        $this->assertNull($schema);
    }

    /*
     * Mocks.
     */

    private function makeRoute(string $controllerClass, string $method): Route
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getControllerClass')->andReturn($controllerClass);
        $route->shouldReceive('getActionMethod')->andReturn($method);

        return $route;
    }
}
