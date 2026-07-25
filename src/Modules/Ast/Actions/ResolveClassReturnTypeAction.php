<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Actions;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;

/**
 * Resolves which class a method returns, if any.
 *
 * @final
 */
class ResolveClassReturnTypeAction
{
    /** @var array<string, class-string|null> */
    private static array $memo = [];

    /**
     * @param  class-string  $className
     * @return class-string|null
     */
    public function execute(string $className, string $methodName): ?string
    {
        $memoKey = sprintf('%s::%s', $className, $methodName);

        if (array_key_exists($memoKey, self::$memo)) {
            return self::$memo[$memoKey];
        }

        return self::$memo[$memoKey] = $this->resolve($className, $methodName);
    }

    /**
     * @param  class-string  $className
     * @return class-string|null
     */
    private function resolve(string $className, string $methodName): ?string
    {
        $methodQuery = ClassQuery::from($className)->method($methodName);

        if (! $methodQuery instanceof MethodQuery) {
            return null;
        }

        $returnType = $methodQuery->getObjectTypeHintReturnType();

        if ($returnType !== null) {
            return $returnType;
        }

        $expr = $methodQuery->getReturnExpression();

        if (! $expr instanceof Expr) {
            return null;
        }

        return $this->resolveClassNameFromExpression($expr);
    }

    /**
     * @return class-string|null
     */
    private function resolveClassNameFromExpression(Expr $expr): ?string
    {
        if ($expr instanceof New_ && $expr->class instanceof Node\Name) {
            // @phpstan-ignore-next-line it is a class-string
            return $expr->class->toString();
        }

        if ($expr instanceof StaticCall && $expr->class instanceof Node\Name) {
            // @phpstan-ignore-next-line it is a class-string
            return $expr->class->toString();
        }

        return null;
    }

    /**
     * Clears static memo cache.
     */
    public static function clearMemo(): void
    {
        self::$memo = [];
    }
}
