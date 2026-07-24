<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sunchayn\Nimbus\Modules\Ast\Actions\LoadClassAstAction;

#[CoversClass(LoadClassAstAction::class)]
class LoadClassAstActionUnitTest extends TestCase
{
    #[DataProvider('classAstResolutionProvider')]
    public function test_it_returns_null_when_class_cannot_be_loaded(string $className): void
    {
        // Arrange

        $loadClassAstAction = new LoadClassAstAction;

        // Act

        $result = $loadClassAstAction->execute($className);

        // Assert

        $this->assertNull($result);
    }

    public static function classAstResolutionProvider(): Generator
    {
        yield 'non existent class' => [
            'className' => 'NonExistentClass',
        ];

        yield 'internal stdClass' => [
            'className' => stdClass::class,
        ];
    }

    public function test_it_loads_ast_for_existing_class(): void
    {
        // Arrange

        $loadClassAstAction = new LoadClassAstAction;

        // Act

        $result = $loadClassAstAction->execute(DummyClassForAst::class);

        // Assert

        $this->assertNotNull($result);

        $classNode = (new NodeFinder)
            ->findFirst($result, function (Node $node) {
                return $node instanceof Class_ && $node->name->toString() === 'DummyClassForAst';
            });

        $this->assertNotNull($classNode);

        $this->assertEquals('DummyClassForAst', $classNode->name->toString());
    }
}

class DummyClassForAst
{
    public string $name = 'test';
}
