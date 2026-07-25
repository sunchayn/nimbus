<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions;

use Generator;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetConcreteValueFromAstExprAction;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ArrayAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ScalarAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;

#[CoversClass(GetConcreteValueFromAstExprAction::class)]
class GetConcreteValueFromAstExprActionUnitTest extends TestCase
{
    #[DataProvider('astExprValueProvider')]
    public function test_it_resolves_concrete_value_from_ast_expression(
        string $exprCode,
        mixed $expectedValue,
        string $expectedValueClass,
        ?VariablesContext $context = null
    ): void {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse('<?php $val = '.$exprCode.';') ?? [];

        $assignNode = (new NodeFinder)->findFirstInstanceOf($stmts, Node\Expr\Assign::class);

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($assignNode->expr, $context);

        // Assert

        $this->assertInstanceOf($expectedValueClass, $result);

        $this->assertEquals($expectedValue, $result->getValue());
    }

    public static function astExprValueProvider(): Generator
    {
        yield 'string literal' => [
            'exprCode' => '"hello"',
            'expectedValue' => 'hello',
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'integer literal' => [
            'exprCode' => '42',
            'expectedValue' => 42,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'float literal' => [
            'exprCode' => '3.14',
            'expectedValue' => 3.14,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'boolean literal' => [
            'exprCode' => 'true',
            'expectedValue' => true,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'associative array' => [
            'exprCode' => '["a" => 1, "b" => "text"]',
            'expectedValue' => ['a' => 1, 'b' => 'text'],
            'expectedValueClass' => ArrayAstContextValue::class,
        ];

        yield 'list array' => [
            'exprCode' => '[10, 20]',
            'expectedValue' => [10, 20],
            'expectedValueClass' => ArrayAstContextValue::class,
        ];

        yield 'string concatenation with context' => [
            'exprCode' => '"prefix_" . $string',
            'expectedValue' => 'prefix_hello',
            'expectedValueClass' => ScalarAstContextValue::class,
            'context' => new VariablesContext([new ScalarAstContextValue(value: 'hello', variableName: 'string')]),
        ];

        yield 'interpolated string with context' => [
            'exprCode' => '"val: {$int}"',
            'expectedValue' => 'val: 42',
            'expectedValueClass' => ScalarAstContextValue::class,
            'context' => new VariablesContext([new ScalarAstContextValue(value: 42, variableName: 'int')]),
        ];

        yield 'class constant fetch' => [
            'exprCode' => '\stdClass::class',
            'expectedValue' => 'stdClass',
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'new object instance' => [
            'exprCode' => 'new \stdClass()',
            'expectedValue' => 'stdClass',
            'expectedValueClass' => ObjectAstContextValue::class,
        ];

        yield 'static call returning scalar' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::getScalar()',
            'expectedValue' => 'static_scalar',
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'static call returning array' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::getArray()',
            'expectedValue' => ['key' => 'val'],
            'expectedValueClass' => ArrayAstContextValue::class,
        ];

        yield 'static call returning object' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::getObject()',
            'expectedValue' => 'stdClass',
            'expectedValueClass' => ObjectAstContextValue::class,
        ];

        yield 'static call with arguments' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::getScalarWithArg("arg_value")',
            'expectedValue' => 'arg_value',
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'static call returning unhandled resource stream' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::getResource()',
            'expectedValue' => null,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'unhandled dynamic variable' => [
            'exprCode' => '$$dynamicVar',
            'expectedValue' => null,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'handled dynamic variable' => [
            'exprCode' => '$$dynamicVar',
            'expectedValue' => 41,
            'expectedValueClass' => ScalarAstContextValue::class,
            'context' => new VariablesContext([
                new ScalarAstContextValue(value: 'foobar', variableName: 'dynamicVar'),
                new ScalarAstContextValue(value: 41, variableName: 'foobar'),
            ]),
        ];

        yield 'static call with variadic placeholder' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::getScalar(...)',
            'expectedValue' => null,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'static call with dynamic method' => [
            'exprCode' => '\Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\StaticCallHelper::$dynamicMethod()',
            'expectedValue' => null,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];

        yield 'new instance with dynamic class' => [
            'exprCode' => 'new $dynamicClass()',
            'expectedValue' => null,
            'expectedValueClass' => ScalarAstContextValue::class,
        ];
    }

    public function test_it_resolves_method_call_on_this_using_class_query(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $classAst = $parser->parse(<<<'PHP'
            <?php
            class DummyController {
                public function action() {
                    return [
                        'password' => $this->passwordRules(),
                    ];
                }

                public function passwordRules(): array {
                    return ['required', 'string'];
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery('DummyController', $classAst);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($classAst, Node\Stmt\ClassMethod::class);

        $returnExpr = $actionNode->stmts[0]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ArrayAstContextValue::class, $result);

        $this->assertEquals(['password' => ['required', 'string']], $result->getValue());
    }

    public function test_it_returns_null_when_method_call_has_no_class_query(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse('<?php $val = $this->passwordRules();');

        $assignNode = (new NodeFinder)->findFirstInstanceOf($stmts, Node\Expr\Assign::class);

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($assignNode->expr);

        // Assert

        $this->assertInstanceOf(ScalarAstContextValue::class, $result);

        $this->assertNull($result->getValue());
    }

    public function test_it_returns_null_for_method_call_on_other_variables_or_dynamic_method_names(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            class DummyController {
                public function action() {
                    $method = 'rules';
                    $a = $other->someMethod();
                    $b = $this->{$method}();
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery('DummyController', $ast);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($ast, Node\Stmt\ClassMethod::class);

        $otherCallExpr = $actionNode->stmts[1]->expr;

        $dynamicCallExpr = $actionNode->stmts[2]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $otherResult = $action->execute($otherCallExpr, classQuery: $classQuery);

        $dynamicResult = $action->execute($dynamicCallExpr, classQuery: $classQuery);

        // Assert

        $this->assertNull($otherResult->getValue());

        $this->assertNull($dynamicResult->getValue());
    }

    public function test_it_returns_null_when_method_does_not_exist_on_class_query(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            class DummyController {
                public function action() {
                    return $this->nonExistentMethod();
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery('DummyController', $ast);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($ast, Node\Stmt\ClassMethod::class);

        $returnExpr = $actionNode->stmts[0]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertNull($result->getValue());
    }

    public function test_it_resolves_scalar_return_value_from_this_method_call(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            class DummyController {
                public function action() {
                    return $this->stringRules();
                }

                public function stringRules(): string {
                    return 'required|string';
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery('DummyController', $ast);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($ast, Node\Stmt\ClassMethod::class);

        $returnExpr = $actionNode->stmts[0]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ScalarAstContextValue::class, $result);

        $this->assertSame('required|string', $result->getValue());
    }

    public function test_it_resolves_object_return_value_from_this_method_call(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            class DummyController {
                public function action() {
                    return $this->objectRules();
                }

                public function objectRules(): object {
                    return new \stdClass();
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery('DummyController', $ast);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($ast, Node\Stmt\ClassMethod::class);

        $returnExpr = $actionNode->stmts[0]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ScalarAstContextValue::class, $result);

        $this->assertSame('stdClass', $result->getValue());
    }

    public function test_it_returns_null_when_this_method_call_uses_dynamic_expression_name(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            class DummyController {
                public function action() {
                    $method = 'rules';
                    return $this->{$method}();
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery(DummyControllerForDynamicTest::class, $ast);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($ast, Node\Stmt\ClassMethod::class);

        $returnExpr = $actionNode->stmts[1]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ScalarAstContextValue::class, $result);

        $this->assertNull($result->getValue());
    }

    public function test_it_resolves_declaring_class_query_from_trait_or_parent_class(): void
    {
        // Arrange

        $classQuery = ClassQuery::from(ControllerWithTraitStub::class);

        $methodQuery = $classQuery->method('action');

        $returnExpr = $methodQuery->getReturnExpression();

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ArrayAstContextValue::class, $result);

        $this->assertEquals(['email' => 'required|email'], $result->getValue());
    }

    public function test_it_catches_reflection_exception_when_method_does_not_exist_on_loaded_class(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions;

            class ControllerWithTraitStub {
                public function action() {
                    return $this->nonExistentReflectionMethod();
                }
            }
            PHP);

        $classQuery = new \Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery(ControllerWithTraitStub::class, $ast);

        $actionNode = (new NodeFinder)->findFirstInstanceOf($ast, Node\Stmt\ClassMethod::class);

        $returnExpr = $actionNode->stmts[0]->expr;

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ScalarAstContextValue::class, $result);

        $this->assertNull($result->getValue());
    }

    public function test_it_resolves_declaring_class_query_from_parent_class(): void
    {
        // Arrange

        $classQuery = ClassQuery::from(ChildControllerStub::class);

        $methodQuery = $classQuery->method('action');

        $returnExpr = $methodQuery->getReturnExpression();

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ArrayAstContextValue::class, $result);

        $this->assertEquals(['parent_field' => 'required|string'], $result->getValue());
    }

    public function test_it_returns_class_query_for_local_method_on_loaded_class(): void
    {
        // Arrange

        $classQuery = ClassQuery::from(StandaloneControllerStub::class);

        $methodQuery = $classQuery->method('action');

        $returnExpr = $methodQuery->getReturnExpression();

        $action = new GetConcreteValueFromAstExprAction;

        // Act

        $result = $action->execute($returnExpr, classQuery: $classQuery);

        // Assert

        $this->assertInstanceOf(ArrayAstContextValue::class, $result);

        $this->assertEquals(['local' => 'required'], $result->getValue());
    }
}

class DummyControllerForDynamicTest
{
    public function action(): void {}
}

trait MethodTraitStub
{
    public function traitRules(): array
    {
        return ['email' => 'required|email'];
    }
}

class ControllerWithTraitStub
{
    use MethodTraitStub;

    public function action(): array
    {
        return $this->traitRules();
    }
}

class ParentControllerStub
{
    public function parentRules(): array
    {
        return ['parent_field' => 'required|string'];
    }
}

class StandaloneControllerStub
{
    public function action(): array
    {
        return $this->localRules();
    }

    public function localRules(): array
    {
        return ['local' => 'required'];
    }
}

class ChildControllerStub extends ParentControllerStub
{
    public function action(): array
    {
        return $this->parentRules();
    }
}

class StaticCallHelper
{
    public static function getScalar(): string
    {
        return 'static_scalar';
    }

    public static function getArray(): array
    {
        return ['key' => 'val'];
    }

    public static function getObject(): object
    {
        return new stdClass;
    }

    public static function getScalarWithArg(string $arg): string
    {
        return $arg;
    }

    public static function getResource()
    {
        return fopen('php://memory', 'r');
    }
}
