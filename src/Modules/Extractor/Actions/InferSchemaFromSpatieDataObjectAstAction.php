<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Converts a Spatie Data class AST into a Schema object.
 *
 * @final
 */
class InferSchemaFromSpatieDataObjectAstAction
{
    public function __construct(
        private readonly InferSchemaFromSpatieDataObjectAction $inferSchemaFromSpatieDataObjectAction,
    ) {}

    /**
     * Resolves Spatie Data object method call expressions.
     */
    public function execute(Expr $expr, ?VariablesContext $context = null): ?Schema
    {
        if (! $this->isSpatieToArrayCall($expr)) {
            return null;
        }

        /** @var Variable $var */
        $var = $expr->var;

        $contextObj = $context ?? VariablesContext::empty();

        /** @var string $varName */
        $varName = $var->name;

        $variable = $contextObj->get($varName);

        $classStr = match (true) {
            $variable instanceof ObjectAstContextValue => $variable->getValue(),
            default => null,
        };

        if ($classStr === null || ! $this->isSpatieDataSubclass($classStr)) {
            return null;
        }

        /** @var class-string $classStr */
        return $this->inferSchemaFromSpatieDataObjectAction->execute($classStr);
    }

    /**
     * @phpstan-assert-if-true MethodCall $expr
     */
    private function isSpatieToArrayCall(Expr $expr): bool
    {
        if (! $expr instanceof MethodCall || ! $expr->name instanceof Identifier) {
            return false;
        }

        $methodName = $expr->name->toString();

        return in_array($methodName, ['toArray', 'all'], true)
            && $expr->var instanceof Variable
            && is_string($expr->var->name);
    }

    /**
     * @phpstan-assert-if-true class-string $classStr
     */
    private function isSpatieDataSubclass(mixed $classStr): bool
    {
        if (! is_string($classStr) || ! class_exists($classStr) || ! class_exists(\Spatie\LaravelData\Data::class)) {
            return false;
        }

        return is_subclass_of($classStr, \Spatie\LaravelData\Data::class) || $classStr === \Spatie\LaravelData\Data::class;
    }
}
