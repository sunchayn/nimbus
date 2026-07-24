<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Queries;

use Generator;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;

#[CoversClass(MethodQuery::class)]
class MethodQueryUnitTest extends TestCase
{
    #[DataProvider('methodQueryScenariosProvider')]
    public function test_it_resolves_return_value_and_context(
        string $methodCode,
        mixed $expectedReturnValue,
        array $expectedContext
    ): void {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse('<?php class Dummy { '.$methodCode.' }');

        $methodNode = (new NodeFinder)->findFirstInstanceOf($stmts, Node\Stmt\ClassMethod::class);

        $query = new MethodQuery($methodNode);

        // Act

        $actualValue = $query->getConcreteReturnValue();

        $actualContext = $query->getLocalContext();

        // Assert

        $this->assertEquals($expectedReturnValue, $actualValue);

        $this->assertEquals($expectedContext, $actualContext->toArray());

        $this->assertEquals('run', $query->getName());
    }

    public static function methodQueryScenariosProvider(): Generator
    {
        yield 'simple literal return' => [
            'methodCode' => 'public function run() { return 42; }',
            'expectedReturnValue' => 42,
            'expectedContext' => [],
        ];

        yield 'return with local variables' => [
            'methodCode' => 'public function run() { $x = "hello"; $y = "world"; return $x . " " . $y; }',
            'expectedReturnValue' => 'hello world',
            'expectedContext' => [
                'x' => 'hello',
                'y' => 'world',
            ],
        ];

        yield 'no return statement' => [
            'methodCode' => 'public function run() { $x = 10; }',
            'expectedReturnValue' => null,
            'expectedContext' => [
                'x' => 10,
            ],
        ];

        yield 'method call assignment ignored' => [
            'methodCode' => 'public function run() { $x = $this->rules(); return 42; }',
            'expectedReturnValue' => 42,
            'expectedContext' => [],
        ];

        yield 'property and array destructuring assignments ignored' => [
            'methodCode' => 'public function run() { $this->x = 10; [$a, $b] = [1, 2]; return 42; }',
            'expectedReturnValue' => 42,
            'expectedContext' => [],
        ];
    }

    #[DataProvider('returnTypeProvider')]
    public function test_it_gets_return_type(?Node\Name $returnType, ?string $expectedType): void
    {
        // Arrange

        $node = new Node\Stmt\ClassMethod(
            name: 'myMethod',
            subNodes: [
                'returnType' => $returnType,
            ],
        );

        $query = new MethodQuery($node);

        // Act

        $type = $query->getObjectTypeHintReturnType();

        // Assert

        $this->assertSame($expectedType, $type);
    }

    public static function returnTypeProvider(): Generator
    {
        yield 'annotated return type' => [
            'returnType' => new Node\Name('\App\Models\User'),
            'expectedType' => 'App\Models\User',
        ];

        yield 'unannotated return type' => [
            'returnType' => null,
            'expectedType' => null,
        ];
    }

    #[DataProvider('parameterMatchingProvider')]
    public function test_it_finds_parameter_matching_type(
        string $phpCode,
        string $type,
        ?string $expectedParameterName
    ): void {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse($phpCode);

        $methodNode = (new NodeFinder)->findFirstInstanceOf($stmts, Node\Stmt\ClassMethod::class);

        $query = new MethodQuery($methodNode);

        // Act

        $actual = $query->findMethodParameterMatchingType($type);

        // Assert

        $this->assertEquals($expectedParameterName, $actual);
    }

    public static function parameterMatchingProvider(): Generator
    {
        yield 'matching parameter type found' => [
            'phpCode' => <<<'PHP'
                <?php
                class Dummy {
                    public function run(\Illuminate\Http\Request $request) {}
                }
                PHP,
            'type' => \Illuminate\Http\Request::class,
            'expectedParameterName' => 'request',
        ];

        yield 'non-matching parameter type returns null' => [
            'phpCode' => <<<'PHP'
                <?php
                class Dummy {
                    public function run(\Illuminate\Http\Request $request) {}
                }
                PHP,
            'type' => \stdClass::class,
            'expectedParameterName' => null,
        ];

        yield 'parameter with union type returns null' => [
            'phpCode' => <<<'PHP'
                <?php
                class Dummy {
                    public function run(string|int $request) {}
                }
                PHP,
            'type' => \Illuminate\Http\Request::class,
            'expectedParameterName' => null,
        ];
    }

    #[DataProvider('methodCallsOnParameterProvider')]
    public function test_it_finds_method_calls_on_parameter_of_type(
        string $phpCode,
        string $type,
        array $methodNames,
        array $expectedCallNames
    ): void {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse($phpCode);

        $methodNode = (new NodeFinder)->findFirstInstanceOf($stmts, Node\Stmt\ClassMethod::class);

        $query = new MethodQuery($methodNode);

        // Act

        $calls = $query->findMethodCallsOnParameterOfType($type, $methodNames);

        // Assert

        $actualCallNames = array_map(fn ($call) => $call->name->toString(), $calls);

        $this->assertEquals($expectedCallNames, $actualCallNames);
    }

    public static function methodCallsOnParameterProvider(): Generator
    {
        yield 'parameter type does not exist returns empty array' => [
            'phpCode' => <<<'PHP'
                <?php
                class Dummy {
                    public function run(\Illuminate\Http\Request $request) {
                        $request->validate([1]);
                    }
                }
                PHP,
            'type' => \stdClass::class,
            'methodNames' => ['validate'],
            'expectedCallNames' => [],
        ];

        yield 'matching parameter type exists, filter single call name' => [
            'phpCode' => <<<'PHP'
                <?php
                class Dummy {
                    public function run(string $id, $untyped, \Illuminate\Http\Request $request) {
                        $request->validate([1]);
                        $request->validateWithBag("bag", [2]);
                    }
                }
                PHP,
            'type' => \Illuminate\Http\Request::class,
            'methodNames' => ['validate'],
            'expectedCallNames' => ['validate'],
        ];

        yield 'matching parameter type exists, filter multiple call names' => [
            'phpCode' => <<<'PHP'
                <?php
                class Dummy {
                    public function run(string $id, $untyped, \Illuminate\Http\Request $request) {
                        $request->validate([1]);
                        $request->validateWithBag("bag", [2]);
                    }
                }
                PHP,
            'type' => \Illuminate\Http\Request::class,
            'methodNames' => ['validate', 'validateWithBag'],
            'expectedCallNames' => ['validate', 'validateWithBag'],
        ];
    }

    public function test_get_local_context_skips_method_static_and_new_assignments(): void
    {
        // Arrange

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse(<<<'PHP'
            <?php
            class Dummy {
                public function run() {
                    $a = "hello";
                    $b = $this->someMethod();
                    $c = StaticClass::someCall();
                    $d = new NewClass();
                }
            }
            PHP);

        $classNode = $ast[0];

        $methodNode = $classNode->stmts[0];

        $query = new MethodQuery($methodNode);

        // Act

        $context = $query->getLocalContext();

        // Assert

        $this->assertTrue($context->has('a'));
        $this->assertSame('hello', $context->get('a')?->getValue());
        $this->assertFalse($context->has('b'));
        $this->assertFalse($context->has('c'));
        $this->assertFalse($context->has('d'));
    }
}
