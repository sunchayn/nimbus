<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Queries;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Ast\Actions\LoadClassAstAction;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ClassQuery::class)]
class ClassQueryFunctionalTest extends TestCase
{
    public function test_it_gets_class_name(): void
    {
        // Arrange

        $query = new ClassQuery('MyClass', []);

        // Act

        $name = $query->className();

        // Assert

        $this->assertEquals('MyClass', $name);
    }

    public function test_it_returns_null_for_non_existent_method(): void
    {
        // Arrange

        $query = new ClassQuery('MyClass', []);

        // Act

        $method = $query->method('nonExistent');

        // Assert

        $this->assertNull($method);
    }

    public function test_it_gets_class_docblock_and_empty_fallback(): void
    {
        // Arrange

        $classNode = new Node\Stmt\Class_(
            name: new Node\Identifier('MyClass'),
            subNodes: [
                'stmts' => [],
            ]
        );

        $classNode->setDocComment(new Doc('/** Description */'));

        $query = new ClassQuery('MyClass', [$classNode]);

        $emptyQuery = new ClassQuery('NoDocClass', []);

        // Act & assert

        // Assert

        $this->assertEquals(
            '/** Description */',
            $query->getClassDocBlock(),
        );

        $this->assertEquals(
            '',
            $emptyQuery->getClassDocBlock(),
        );
    }

    public function test_it_indexes_methods_and_resolves_qualified_namespaced_class(): void
    {
        // Arrange

        $methodNode = new Node\Stmt\ClassMethod('myMethod');

        $classNode = new Node\Stmt\Class_(
            name: new Node\Identifier('MyClass'),
            subNodes: [
                'stmts' => [$methodNode],
            ]
        );

        $classNode->namespacedName = new Node\Name('App\\Controllers\\MyClass');

        $query = new ClassQuery('App\\Controllers\\MyClass', [$classNode]);

        // Act

        $methodQuery = $query->method('myMethod');

        // Assert

        $this->assertInstanceOf(MethodQuery::class, $methodQuery);

        $this->assertEquals('myMethod', $methodQuery->getName());
    }

    public function test_it_can_be_created_from_static_factory(): void
    {
        // Anticipate

        $this->mock(
            LoadClassAstAction::class,
            function ($mock): void {
                $mock->shouldReceive('execute')->once()->andReturn([]);
            },
        );

        // Act

        $query = ClassQuery::from('Namespaced\\Sub\\SomeClass');

        // Assert

        $this->assertEquals('Namespaced\\Sub\\SomeClass', $query->className());
        $this->assertEquals('SomeClass', $query->classShortName());
    }

    public function test_it_indexes_methods_on_trait_nodes(): void
    {
        // Arrange

        $methodNode = new Node\Stmt\ClassMethod('traitMethod');

        $traitNode = new Node\Stmt\Trait_(
            name: new Node\Identifier('MyTrait'),
            subNodes: [
                'stmts' => [$methodNode],
            ]
        );

        $traitNode->namespacedName = new Node\Name('App\\Traits\\MyTrait');

        $query = new ClassQuery('App\\Traits\\MyTrait', [$traitNode]);

        // Act

        $methodQuery = $query->method('traitMethod');

        // Assert

        $this->assertInstanceOf(MethodQuery::class, $methodQuery);

        $this->assertEquals('traitMethod', $methodQuery->getName());
    }

    public function test_it_indexes_methods_on_enum_nodes(): void
    {
        // Arrange

        $methodNode = new Node\Stmt\ClassMethod('enumMethod');

        $enumNode = new Node\Stmt\Enum_(
            name: new Node\Identifier('MyEnum'),
            subNodes: [
                'stmts' => [$methodNode],
            ]
        );

        $enumNode->namespacedName = new Node\Name('App\\Enums\\MyEnum');

        $query = new ClassQuery('App\\Enums\\MyEnum', [$enumNode]);

        // Act

        $methodQuery = $query->method('enumMethod');

        // Assert

        $this->assertInstanceOf(MethodQuery::class, $methodQuery);

        $this->assertEquals('enumMethod', $methodQuery->getName());
    }
}
