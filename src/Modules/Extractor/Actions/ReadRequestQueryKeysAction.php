<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;

/**
 * Reads the input keys a controller method asks from the request, such as $request->query('page').
 *
 * Only literal string keys are read. Dotted keys are skipped because they describe nested input.
 *
 * @final
 */
class ReadRequestQueryKeysAction
{
    private const READ_METHODS = ['query', 'input', 'has', 'only'];

    /**
     * @param  class-string  $controllerClass
     * @return string[]
     */
    public function execute(string $controllerClass, string $method): array
    {
        $methodNode = ClassQuery::from($controllerClass)->method($method)?->methodNode;

        if (! $methodNode instanceof \PhpParser\Node\Stmt\ClassMethod) {
            return [];
        }

        $calls = (new NodeFinder)->find(
            $methodNode->stmts ?? [],
            fn (Node $node): bool => $node instanceof MethodCall
                && $node->var instanceof Variable
                && $node->var->name !== 'this'
                && $node->name instanceof Node\Identifier
                && in_array($node->name->toString(), self::READ_METHODS, true),
        );

        $keys = [];

        foreach ($calls as $call) {
            $argument = $call instanceof MethodCall ? ($call->args[0]->value ?? null) : null;

            foreach ($this->literalKeys($argument) as $key) {
                if ($key !== '' && ! str_contains($key, '.')) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return string[]
     */
    private function literalKeys(?Node $argument): array
    {
        if ($argument instanceof String_) {
            return [$argument->value];
        }

        if (! $argument instanceof Node\Expr\Array_) {
            return [];
        }

        $keys = [];

        foreach ($argument->items as $item) {
            if ($item->value instanceof String_) {
                $keys[] = $item->value->value;
            }
        }

        return $keys;
    }
}
