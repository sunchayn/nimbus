<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use Generator;
use Mockery\MockInterface;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\LaravelData\Data;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromSpatieDataObjectAstAction;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Request\Strategies\Stubs\SpatieDataObjectStub;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(InferSchemaFromSpatieDataObjectAstAction::class)]
class InferSchemaFromSpatieDataObjectAstActionUnitTest extends TestCase
{
    private InferSchemaFromSpatieDataObjectAction|MockInterface $inferSchemaFromSpatieDataObjectActionMock;

    private InferSchemaFromSpatieDataObjectAstAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inferSchemaFromSpatieDataObjectActionMock = $this->mock(InferSchemaFromSpatieDataObjectAction::class);

        $this->action = new InferSchemaFromSpatieDataObjectAstAction($this->inferSchemaFromSpatieDataObjectActionMock);
    }

    #[DataProvider('unsupportedMethodCallProvider')]
    public function test_it_returns_null_when_resolution_fails(
        Expr $expr,
        ?VariablesContext $context = null
    ): void {
        // Act

        $result = $this->action->execute($expr, $context);

        // Assert

        $this->assertNull($result);
    }

    public static function unsupportedMethodCallProvider(): Generator
    {
        yield 'expression is not method call' => [
            'expr' => new Variable('myVar'),
            'context' => null,
        ];

        yield 'method name is unsupported' => [
            'expr' => new MethodCall(new Variable('myVar'), new Identifier('customMethod')),
            'context' => new VariablesContext(['myVar' => new ObjectAstContextValue('myVar', 'SomeClass')]),
        ];

        yield 'variable type not in context' => [
            'expr' => new MethodCall(new Variable('dto'), new Identifier('toArray')),
            'context' => VariablesContext::empty(),
        ];

        yield 'variable is dynamic expression' => [
            'expr' => new MethodCall(new MethodCall(new Variable('sub'), new Identifier('get')), new Identifier('toArray')),
            'context' => VariablesContext::empty(),
        ];

        yield 'class in context is not spatie data subclass' => [
            'expr' => new MethodCall(new Variable('dto'), new Identifier('toArray')),
            'context' => new VariablesContext([new ObjectAstContextValue(className: \stdClass::class, variableName: 'dto')]),
        ];

        yield 'class in context does not exist' => [
            'expr' => new MethodCall(new Variable('dto'), new Identifier('toArray')),
            'context' => new VariablesContext([new ObjectAstContextValue(className: 'NonExistentClass', variableName: 'dto')]),
        ];
    }

    public function test_it_transforms_spatie_data_method_call_when_class_matches(): void
    {
        // Arrange

        if (! class_exists(Data::class)) {
            $this->markTestSkipped('Spatie Laravel Data is not installed.');
        }

        $expr = new MethodCall(new Variable('dto'), new Identifier('toArray'));

        $expectedSchema = new Schema([]);

        $stubClass = SpatieDataObjectStub::class;

        // Anticipate

        $this->inferSchemaFromSpatieDataObjectActionMock
            ->shouldReceive('execute')
            ->once()
            ->with($stubClass)
            ->andReturn($expectedSchema);

        // Act

        $result = $this->action->execute(
            $expr,
            new VariablesContext([
                new ObjectAstContextValue(className: $stubClass, variableName: 'dto'),
            ]),
        );

        // Assert

        $this->assertSame($expectedSchema, $result);
    }
}
