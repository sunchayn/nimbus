<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Queries;

use Illuminate\Support\Arr;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use Sunchayn\Nimbus\Modules\Ast\Actions\LoadClassAstAction;

/**
 * Provides high-level AST queries over a single PHP class.
 *
 * @example ::from(UserResource::class)->method('toArray')?->getReturnExpression()
 * @example ::from(CreateUserRequest::class)->method('rules')?->resolveReturnValue()
 */
class ClassQuery
{
    /** @var array<string, ClassMethod> */
    private array $methods = [];

    private ?Node\Stmt\ClassLike $classLike = null;

    /**
     * @param  class-string  $className
     * @param  Node[]  $stmts
     */
    public function __construct(
        private readonly string $className,
        private readonly array $stmts,
    ) {
        $this->preprocessClassNodes();
    }

    /**
     * Builds a ClassQuery for the given class name by parsing its source file.
     *
     * @param  class-string  $className
     */
    public static function from(string $className): self
    {
        return new self(
            className: $className,
            stmts: resolve(LoadClassAstAction::class)->execute($className) ?? [],
        );
    }

    public function className(): string
    {
        return $this->className;
    }

    public function classShortName(): string
    {
        $parts = explode('\\', $this->className);

        return end($parts);
    }

    public function method(string $name): ?MethodQuery
    {
        if (! array_key_exists($name, $this->methods)) {
            return null;
        }

        return new MethodQuery($this->methods[$name]);
    }

    public function getClassDocBlock(): string
    {
        $docComment = $this->classLike?->getDocComment();

        return $docComment instanceof Doc ? $docComment->getText() : '';
    }

    /**
     * Traverses the parsed AST to find and index all ClassMethod nodes.
     */
    private function preprocessClassNodes(): void
    {
        $classes = (new NodeFinder)->findInstanceOf($this->stmts, Node\Stmt\ClassLike::class);

        $this->classLike = Arr::first(
            $classes,
            fn (Node\Stmt\ClassLike $class): bool => in_array(
                $this->getQualifiedNameOf($class),
                [$this->className, class_basename($this->className)],
                true
            ),
        );

        if ($this->classLike === null) {
            return;
        }

        foreach ($this->classLike->stmts as $stmt) {
            // Methods are indexed by name at construction time,
            // so we can avoid re-traversing the AST on every method() call.
            if ($stmt instanceof ClassMethod) {
                $this->methods[$stmt->name->toString()] = $stmt;
            }
        }
    }

    /**
     * Extracts the fully qualified class name of a ClassLike node.
     */
    private function getQualifiedNameOf(Node\Stmt\ClassLike $class): ?string
    {
        $name = $class->namespacedName ?? null;

        if (! $name instanceof Name) {
            return $class->name?->toString();
        }

        return $name->toString();
    }
}
