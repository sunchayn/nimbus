<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Services\Response\Strategies;

use Generator;
use Illuminate\Routing\Route;
use Mockery;
use Mockery\MockInterface;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Variable;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Ast\Actions\LoadClassAstAction;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Actions\ResolveMethodVariableContextAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies\RawJsonResponseStrategy;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(RawJsonResponseStrategy::class)]
class RawJsonResponseStrategyFunctionalTest extends TestCase
{
    private LoadClassAstAction|MockInterface $loadClassAstActionMock;

    private RawJsonResponseStrategy $strategy;

    private InferSchemaFromArrayAstAction|MockInterface $inferSchemaFromArrayAstActionMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadClassAstActionMock = $this->mock(LoadClassAstAction::class);

        $this->inferSchemaFromArrayAstActionMock = $this->mock(InferSchemaFromArrayAstAction::class);

        $this->loadClassAstActionMock
            ->shouldReceive('execute')
            ->byDefault()
            ->andReturn([]);

        $methodVariableResolverMock = $this->mock(ResolveMethodVariableContextAction::class);

        $methodVariableResolverMock
            ->shouldReceive('execute')
            ->byDefault()
            ->andReturn([]);

        $this->strategy = resolve(RawJsonResponseStrategy::class);
    }

    #[DataProvider('nullArrayNodeProvider')]
    public function test_it_returns_null_when_array_cannot_be_extracted(?Route $route, ?string $phpCode = null): void
    {
        // Arrange

        $routeMock = $route ?? $this->makeRoute($phpCode);

        // Act & Assert

        $this->assertNull($this->strategy->attempt($routeMock));
    }

    public static function nullArrayNodeProvider(): Generator
    {
        yield 'no array node found' => [
            'route' => null,
            'phpCode' => <<<'PHP'
                <?php
                class DummyController {
                    public function method() {
                        return true;
                    }
                }
                PHP,
        ];

        yield 'closure route' => [
            'route' => new Route(['GET'], '/closure-route', fn () => 'ok'),
        ];

        yield 'method does not exist' => [
            'route' => new Route(['GET'], '/test', [RawJsonResponseStrategyFunctionalTest::class, 'nonExistentMethod']),
        ];

        yield 'empty return statement found' => [
            'route' => null,
            'phpCode' => <<<'PHP'
                <?php
                class DummyController {
                    public function method() {
                        return;
                    }
                }
                PHP,
        ];

        yield 'response json has no args' => [
            'route' => null,
            'phpCode' => <<<'PHP'
                <?php
                class DummyController {
                    public function method() {
                        return response()->json();
                    }
                }
                PHP,
        ];
    }

    public function test_it_resolves_response_shape_from_inline_validation_variables(): void
    {
        // Arrange

        $phpCode = <<<'PHP'
            <?php
            class DummyController {
                public function method(\Illuminate\Http\Request $request) {
                    $validated = $request->validate([
                        'name' => 'required|string',
                        'age' => 'required|integer',
                    ]);

                    return response()->json([
                        'message' => 'Passed',
                        'data' => $validated,
                    ]);
                }
            }
            PHP;

        $routeMock = $this->makeRoute($phpCode);

        $strategy = resolve(RawJsonResponseStrategy::class);

        // Anticipate

        $this->inferSchemaFromArrayAstActionMock
            ->allows('execute')
            ->andReturn(
                $schema = new Schema([new StringSchemaProperty(name: 'foobar')]),
            );

        // Act

        $actual = $strategy->attempt($routeMock);

        // Assert

        $this->assertNotNull($schema);

        $this->assertSame(
            $schema,
            $actual,
        );

        $this
            ->inferSchemaFromArrayAstActionMock
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (Array_ $arrayNodeArg, VariablesContext $contextArg) {
                $this->assertCount(2, $arrayNodeArg->items);

                $this->assertEquals(
                    'message',
                    $arrayNodeArg->items[0]->key->value
                );

                $this->assertEquals(
                    'Passed',
                    $arrayNodeArg->items[0]->value->value
                );

                $this->assertEquals(
                    'data',
                    $arrayNodeArg->items[1]->key->value
                );

                $this->assertEquals(
                    'validated',
                    $arrayNodeArg->items[1]->value->name
                );

                $this->assertInstanceOf(
                    Variable::class,
                    $arrayNodeArg->items[1]->value
                );

                $this->assertEmpty($contextArg->items);

                return true;
            });
    }

    /*
     * Mocks.
     */

    private function makeRoute(string $phpCode): Route
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse($phpCode) ?? [];

        return $this->makeRouteWithStmts($stmts);
    }

    private function makeRouteWithStmts(array $stmts): Route
    {
        $routeMock = Mockery::mock(Route::class);
        $routeMock->shouldReceive('getActionMethod')->andReturn('method');
        $routeMock->shouldReceive('getControllerClass')->andReturn('DummyController');

        $this->loadClassAstActionMock
            ->shouldReceive('execute')
            ->with('DummyController')
            ->andReturn($stmts);

        return $routeMock;
    }
}
