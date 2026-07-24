<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetPhpTypeFromAstScalarAction;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ArrayAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ScalarAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Converts an AST Array_ (associative) node into a Schema object.
 *
 * @final
 */
class InferSchemaFromArrayAstAction
{
    public function __construct(
        private readonly InferSchemaFromSpatieDataObjectAstAction $inferSchemaFromSpatieDataObjectAstAction,
        private readonly GetPhpTypeFromAstScalarAction $getPhpTypeFromAstScalarAction,
        private readonly InferSchemaFromArrayAction $inferSchemaFromArrayAction,
    ) {}

    public function execute(Array_ $arrayNode, ?VariablesContext $context = null): Schema
    {
        $contextObj = $context ?? VariablesContext::empty();

        $map = $this->process($arrayNode, $contextObj);

        return Schema::fromArrayMap($map);
    }

    /**
     * @return array<string, SchemaPropertyInterface|Schema>
     */
    private function process(Array_ $array, VariablesContext $context): array
    {
        $map = [];

        foreach ($array->items as $item) {
            /** @var Node\ArrayItem|null $item */
            if ($item === null || ! $item->key instanceof Node\Scalar\String_) {
                continue;
            }

            $key = $item->key->value;

            $map[$key] = $this->resolveTypeFromExpression($item->value, $key, $context);
        }

        return $map;
    }

    /**
     * Resolves type definitions from AST expressions.
     */
    private function resolveTypeFromExpression(Node\Expr $expr, string $key, VariablesContext $context): SchemaPropertyInterface|Schema
    {
        if ($expr instanceof Array_) {
            $map = $this->process($expr, $context);

            return Schema::fromArrayMap($map);
        }

        return $this->attemptGettingValueFromVariable($expr, $key, $context)
            ?? $this->inferSchemaFromSpatieDataObjectAstAction->execute($expr, $context)
            ?? $this->attemptGettingTypeFromScalar($expr, $key)
            ?? new StringSchemaProperty($key, required: true);
    }

    private function attemptGettingValueFromVariable(Expr $expr, string $key, VariablesContext $context): SchemaPropertyInterface|Schema|null
    {
        if (! $expr instanceof Expr\Variable || ! is_string($expr->name)) {
            return null;
        }

        $variable = $context->get($expr->name);

        if ($variable instanceof ArrayAstContextValue) {
            return $this->inferSchemaFromArrayAction->execute($variable->getValue());
        }

        if ($variable instanceof ObjectAstContextValue) {
            return new ObjectSchemaProperty($key, required: true, schema: Schema::empty());
        }

        if ($variable instanceof ScalarAstContextValue) {
            return $this->buildPropertyFromValue($key, $variable->getValue());
        }

        return null;
    }

    private function attemptGettingTypeFromScalar(Node\Expr $expr, string $key): ?SchemaPropertyInterface
    {
        $phpType = $this->getPhpTypeFromAstScalarAction->execute($expr);

        if (! is_string($phpType)) {
            return null;
        }

        $schemaType = SchemaPropertyType::tryFrom($phpType);

        return match ($schemaType) {
            SchemaPropertyType::INTEGER => new IntegerSchemaProperty($key, required: true),
            SchemaPropertyType::NUMBER => new NumberSchemaProperty($key, required: true),
            SchemaPropertyType::BOOLEAN => new BooleanSchemaProperty($key, required: true),
            default => new StringSchemaProperty($key, required: true),
        };
    }

    private function buildPropertyFromValue(string $key, mixed $value): SchemaPropertyInterface
    {
        if (is_int($value)) {
            return new IntegerSchemaProperty($key, required: true);
        }

        if (is_float($value)) {
            return new NumberSchemaProperty($key, required: true);
        }

        if (is_bool($value)) {
            return new BooleanSchemaProperty($key, required: true);
        }

        return new StringSchemaProperty($key, required: true, nullable: $value === null);
    }
}
