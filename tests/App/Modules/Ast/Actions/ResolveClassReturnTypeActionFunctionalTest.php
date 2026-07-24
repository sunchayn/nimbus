<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions;

use Generator;
use Mockery\MockInterface;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Ast\Actions\LoadClassAstAction;
use Sunchayn\Nimbus\Modules\Ast\Actions\ResolveClassReturnTypeAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResolveClassReturnTypeAction::class)]
class ResolveClassReturnTypeActionFunctionalTest extends TestCase
{
    private LoadClassAstAction|MockInterface $loadClassAstActionMock;

    private ResolveClassReturnTypeAction $resolveClassReturnTypeAction;

    protected function setUp(): void
    {
        parent::setUp();

        ResolveClassReturnTypeAction::clearMemo();

        $this->loadClassAstActionMock = $this->mock(LoadClassAstAction::class);

        $this->resolveClassReturnTypeAction = new ResolveClassReturnTypeAction;
    }

    protected function tearDown(): void
    {
        ResolveClassReturnTypeAction::clearMemo();

        parent::tearDown();
    }

    public function test_it_memoizes_resolution_results(): void
    {
        // Arrange

        $phpCode = <<<'PHP'
            <?php
            class MemoController {
                public function show(): \Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs\DummyUserResource {
                    return new \Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs\DummyUserResource();
                }
            }
            PHP;

        $stmts = (new ParserFactory)->createForNewestSupportedVersion()->parse($phpCode) ?? [];

        // Anticipate

        $this->loadClassAstActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('MemoController')
            ->andReturn($stmts);

        // Act

        $first = $this->resolveClassReturnTypeAction->execute('MemoController', 'show');

        $second = $this->resolveClassReturnTypeAction->execute('MemoController', 'show');

        // Assert

        $this->assertEquals(Stubs\DummyUserResource::class, $first);

        $this->assertEquals($first, $second);
    }

    #[DataProvider('resolutionProvider')]
    public function test_it_resolves_class_return_type(
        string $className,
        string $methodName,
        ?string $expectedResult,
        ?string $phpCode = null
    ): void {
        // Arrange

        if ($phpCode !== null) {
            $stmts = (new ParserFactory)->createForNewestSupportedVersion()->parse($phpCode) ?? [];

            $this->loadClassAstActionMock
                ->shouldReceive('execute')
                ->with($className)
                ->andReturn($stmts);
        }

        // Act

        $actual = $this->resolveClassReturnTypeAction->execute($className, $methodName);

        // Assert

        $this->assertEquals($expectedResult, $actual);
    }

    public static function resolutionProvider(): Generator
    {
        yield 'resolves via return type hint' => [
            'className' => 'TypeHintedController',
            'methodName' => 'show',
            'expectedResult' => Stubs\DummyUserResource::class,
            'phpCode' => <<<'PHP'
                <?php
                class TypeHintedController {
                    public function show(): \Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs\DummyUserResource {
                        return new \Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs\DummyUserResource();
                    }
                }
                PHP,
        ];

        yield 'resolves via AST new expression' => [
            'className' => 'NewExprController',
            'methodName' => 'show',
            'expectedResult' => Stubs\DummyUserResource::class,
            'phpCode' => <<<'PHP'
                <?php
                class NewExprController {
                    public function show() {
                        return new \Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs\DummyUserResource();
                    }
                }
                PHP,
        ];

        yield 'resolves via AST static collection call' => [
            'className' => 'StaticCollectionController',
            'methodName' => 'index',
            'expectedResult' => Stubs\DummyUserResource::class,
            'phpCode' => <<<'PHP'
                <?php
                class StaticCollectionController {
                    public function index() {
                        return \Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs\DummyUserResource::collection([]);
                    }
                }
                PHP,
        ];

        yield 'returns null when method does not exist' => [
            'className' => 'MissingMethodController',
            'methodName' => 'nonExistentMethod',
            'expectedResult' => null,
            'phpCode' => <<<'PHP'
                <?php
                class MissingMethodController {}
                PHP,
        ];

        yield 'returns null when return expression is not new or static call' => [
            'className' => 'VarReturnController',
            'methodName' => 'show',
            'expectedResult' => null,
            'phpCode' => <<<'PHP'
                <?php
                class VarReturnController {
                    public function show() {
                        return $var;
                    }
                }
                PHP,
        ];

        yield 'returns null when return statement has no expression' => [
            'className' => 'EmptyReturnController',
            'methodName' => 'show',
            'expectedResult' => null,
            'phpCode' => <<<'PHP'
                <?php
                class EmptyReturnController {
                    public function show() {
                        return;
                    }
                }
                PHP,
        ];
    }
}

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions\Stubs;

use Illuminate\Http\Resources\Json\JsonResource;

class DummyUserResource extends JsonResource {}
