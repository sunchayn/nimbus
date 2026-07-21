<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast;

use Generator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\ConvertNodeToConcreteValue;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\ValidateCallVisitor;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Ast\Stubs\SideEffectStub;
use Sunchayn\Nimbus\Tests\App\Modules\Routes\Extractors\Stubs\StatusEnumStub;

#[CoversClass(ValidateCallVisitor::class)]
#[CoversClass(ConvertNodeToConcreteValue::class)]
class ValidateCallVisitorUnitTest extends TestCase
{
    #[DataProvider('scenariosDataProvider')]
    public function test_it_works(
        string $methodName,
        string $phpCode,
        array $expectedRules,
    ): void {
        // Arrange

        // Parse the stub into AST
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($phpCode);

        $visitor = new ValidateCallVisitor($methodName);

        $traverser = new NodeTraverser;

        $traverser->addVisitor($visitor);

        // Act

        $traverser->traverse($ast);

        // Assert

        $this->assertEquals(Ruleset::fromLaravelRules($expectedRules), $visitor->getRules());
    }

    public static function scenariosDataProvider(): Generator
    {
        yield 'simple call' => [
            'methodName' => 'simple_call',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
                'foobaz' => ['required', 'integer'],
            ],
        ];

        yield 'simple call without variable assignment' => [
            'methodName' => 'simple_call_without_assignment',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
                'foobaz' => ['required', 'integer'],
            ],
        ];

        yield 'rules from sub method, concatenation, and interpolation' => [
            'methodName' => 'getting_rules_from_sub_method',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required_with:foobaz',
                'foobarr' => 'present_with:foobar',
                'fooobazz' => 'required|string|email|max:200',
                'baz' => 'required|string|present|email',
                'foobaz' => ['required', 'string', 'email'],
            ],
        ];

        yield 'call from a variable' => [
            'methodName' => 'call_from_a_variable',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
                'foobaz' => ['required', 'integer'],
            ],
        ];

        yield 'call from nested variables' => [
            'methodName' => 'call_from_nested_variables',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
                'foobaz' => ['required', 'string', 'email'],
            ],
        ];

        yield 'call with rules instances' => [
            'methodName' => 'call_with_rules_instances',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => [
                    'required',
                    Rule::in(1, 2, 3, 4),
                    Rule::notIn(3, 4),
                    null, // <- RequiredIf cannot be resolved with a closure.
                    Rule::enum(StatusEnumStub::class),
                ],
                'foobaz' => ['required', 'string', new In(1, 2)],
            ],
        ];

        yield 'call with validateWithBag' => [
            'methodName' => 'call_validateWithBag',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => [
                    'required',
                    'in:1, 2, 3, 4',
                ],
                'foobaz' => ['required', 'string', 'email'],
            ],
        ];

        yield 'no validate call' => [
            'methodName' => 'no_validate_call',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [],
        ];

        yield 'calling validate on a non-request class' => [
            'methodName' => 'call_validate_on_different_class',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [],
        ];

        yield 'nested methods call' => [
            'methodName' => 'nested_methods_calls',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [], // <- Currently this is not supported.
        ];

        yield 'static call assignment with side effects' => [
            'methodName' => 'call_with_static_side_effect_assignment',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
            ],
        ];

        yield 'new instance assignment with side effects' => [
            'methodName' => 'call_with_new_side_effect_assignment',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
            ],
        ];

        yield 'static keyword assignment' => [
            'methodName' => 'call_with_static_keyword_assignment',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'foobar' => 'required|string',
            ],
        ];

        yield 'rules from self:: static call' => [
            'methodName' => 'call_with_self_validation_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'name' => 'required|string',
                'email' => 'required|email',
            ],
        ];

        yield 'rules from static:: static call' => [
            'methodName' => 'call_with_static_validation_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'name' => 'required|string',
                'email' => 'required|email',
            ],
        ];

        yield 'rules from explicit class static call' => [
            'methodName' => 'call_with_explicit_class_validation_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [
                'name' => 'required|string',
                'email' => 'required|email',
            ],
        ];

        yield 'unknown self:: helper yields no rules' => [
            'methodName' => 'call_with_unknown_self_validation_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [],
        ];

        yield 'variable class static call yields no rules' => [
            'methodName' => 'call_with_variable_class_static_validation_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [],
        ];

        yield 'variable method nested call yields no rules' => [
            'methodName' => 'call_with_variable_method_nested_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [],
        ];

        yield 'foreign class static call with matching method name yields no rules' => [
            'methodName' => 'call_with_foreign_class_validation_rules',
            'phpCode' => file_get_contents(__DIR__.'/Stubs/controller.stub.php'),
            'expectedRules' => [],
        ];
    }

    public function test_it_uses_short_class_name_when_namespaced_name_is_unavailable(): void
    {
        // Arrange — bypass NameResolver so Class_::namespacedName is unset.

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse(<<<'PHP'
            <?php

            class CoverageController
            {
                public function action(): void
                {
                }
            }
            PHP);

        $visitor = new ValidateCallVisitor('action');
        $reflection = new ReflectionClass($visitor);
        $gatherClassMethods = $reflection->getMethod('gatherClassMethods');
        $gatherClassMethods->setAccessible(true);

        // Act

        $gatherClassMethods->invoke($visitor, $ast);

        // Assert

        $currentClassName = $reflection->getProperty('currentClassName');
        $currentClassName->setAccessible(true);

        $this->assertSame('CoverageController', $currentClassName->getValue($visitor));
    }

    public function test_it_rejects_static_calls_when_enclosing_class_name_is_unknown(): void
    {
        // Arrange

        $visitor = new ValidateCallVisitor('action');
        $reflection = new ReflectionClass($visitor);
        $isCurrentClassReference = $reflection->getMethod('isCurrentClassReference');
        $isCurrentClassReference->setAccessible(true);

        // Act — currentClassName stays null when the enclosing class cannot be named.

        $result = $isCurrentClassReference->invoke($visitor, new Name('SomeOtherClass'));

        // Assert

        $this->assertFalse($result);
    }

    #[DataProvider('sideEffectScenariosDataProvider')]
    public function test_it_does_not_execute_application_code_while_building_variable_context(
        string $methodName,
    ): void {
        // Arrange

        SideEffectStub::reset();

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse(file_get_contents(__DIR__.'/Stubs/controller.stub.php'));

        $visitor = new ValidateCallVisitor($methodName);
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);

        // Act

        $traverser->traverse($ast);

        // Assert

        $this->assertFalse(
            SideEffectStub::$called,
            'Route extraction must not execute assigned StaticCall/New_ expressions.',
        );
        $this->assertEquals(
            Ruleset::fromLaravelRules(['foobar' => 'required|string']),
            $visitor->getRules(),
        );
    }

    public static function sideEffectScenariosDataProvider(): Generator
    {
        yield 'static call assignment' => [
            'methodName' => 'call_with_static_side_effect_assignment',
        ];

        yield 'new instance assignment' => [
            'methodName' => 'call_with_new_side_effect_assignment',
        ];
    }
}
