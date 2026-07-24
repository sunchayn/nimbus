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
