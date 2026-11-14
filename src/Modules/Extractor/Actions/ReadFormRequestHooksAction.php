<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Extractor\DataTransferObjects\FormRequestHooksData;

/**
 * Reads prepareForValidation(), attributes(), and authorize() of a form request from its source.
 *
 * @final
 */
class ReadFormRequestHooksAction
{
    /**
     * @param  class-string  $requestClass
     */
    public function execute(string $requestClass): FormRequestHooksData
    {
        $classQuery = ClassQuery::from($requestClass);

        $prepare = $classQuery->method('prepareForValidation')?->methodNode;

        $authorizeReturn = $classQuery->method('authorize')?->getReturnExpression();

        $attributes = $classQuery->method('attributes')?->getConcreteReturnValue();

        return new FormRequestHooksData(
            mergedKeys: $prepare instanceof ClassMethod ? $this->findMergedKeys($prepare) : [],
            labels: is_array($attributes) ? $this->onlyStringLabels($attributes) : [],
            authorizeReturnsFalse: $authorizeReturn instanceof ConstFetch && strtolower($authorizeReturn->name->toString()) === 'false',
        );
    }

    /**
     * @return string[]
     */
    private function findMergedKeys(ClassMethod $method): array
    {
        $calls = (new NodeFinder)->find(
            $method->stmts ?? [],
            fn (Node $node): bool => $node instanceof MethodCall
                && $node->var instanceof Node\Expr\Variable
                && $node->var->name === 'this'
                && $node->name instanceof Node\Identifier
                && $node->name->toString() === 'merge',
        );

        $keys = [];

        foreach ($calls as $call) {
            $array = $call instanceof MethodCall ? ($call->args[0]->value ?? null) : null;

            if (! $array instanceof Node\Expr\Array_) {
                continue;
            }

            foreach ($array->items as $item) {
                if ($item->key instanceof Node\Scalar\String_) {
                    $keys[] = $item->key->value;
                }
            }
        }

        return $keys;
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     * @return array<string, string>
     */
    private function onlyStringLabels(array $attributes): array
    {
        return array_filter(
            $attributes,
            fn (mixed $label, mixed $field): bool => is_string($label) && is_string($field),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
