<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies;

use Illuminate\Routing\Route;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromArrayAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Contracts\ResponseSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Extracts a response Schema from controller methods that return a raw JSON array.
 *
 * Handles two possible inline patterns:
 *  - `return ['key' => 'value']` -> direct literal array
 *  - `return response()->json(['key' => 'value'])` -> array passed to `json()`
 */
class RawJsonResponseStrategy implements ResponseSchemaStrategyContract
{
    public function __construct(
        private readonly InferSchemaFromArrayAstAction $inferSchemaFromArrayAstAction,
    ) {}

    public function attempt(Route $route): ?Schema
    {
        /** @var class-string $controllerClass */
        $controllerClass = $route->getControllerClass();

        $methodName = $route->getActionMethod();

        if (empty($controllerClass) || empty($methodName)) {
            return null;
        }

        $methodQuery = ClassQuery::from($controllerClass)->method($methodName);

        if (! $methodQuery instanceof MethodQuery) {
            return null;
        }

        $returnExpr = $methodQuery->getReturnExpression();

        if (! $returnExpr instanceof Expr) {
            return null;
        }

        $arrayNode = $this->findRelevantArrayNode($returnExpr);

        if (! $arrayNode instanceof Array_) {
            return null;
        }

        $schema = $this->inferSchemaFromArrayAstAction->execute(
            $arrayNode,
            context: $methodQuery->getLocalContext(),
        );

        return $schema->isEmpty() ? null : $schema;
    }

    private function findRelevantArrayNode(Expr $expr): ?Array_
    {
        if ($expr instanceof MethodCall && $expr->name instanceof Node\Identifier && $expr->name->toString() === 'json') {
            // @todo [ENHANCEMENT] also check that where are calling ->json() on an instead of response()
            $expr = $expr->args[0]->value ?? null;
        }

        return $expr instanceof Array_ ? $expr : null;
    }
}
