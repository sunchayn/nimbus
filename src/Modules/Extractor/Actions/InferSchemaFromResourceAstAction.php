<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetPhpTypeFromAstScalarAction;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Extractor\DataTransferObjects\NestedResourceTarget;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\NumberSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\OptionalSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Converts a Laravel JsonResource class AST into a Schema object
 * by statically analyzing its toArray() method body.
 *
 * @final
 */
class InferSchemaFromResourceAstAction
{
    private const CONDITIONAL_HELPERS = [
        'when',
        'unless',
        'whenLoaded',
        'whenNotNull',
        'whenHas',
        'whenCounted',
        'whenAggregated',
        'whenPivotLoaded',
        'whenPivotLoadedAs',
    ];

    /**
     * @var array<class-string<JsonResource>, ClassQuery> Tells if a resource was already visited or not in the current pass to avoid endless loop.
     */
    private array $currentIterationResources = [];

    public function __construct(
        private readonly GetPhpTypeFromAstScalarAction $getPhpTypeFromAstScalarAction,
        private readonly InferEloquentModelFromJsonResourceAction $inferEloquentModelFromJsonResourceAction,
        private readonly InferSchemaPropertyFromDatabaseColumnAction $inferSchemaPropertyFromDatabaseColumnAction,
    ) {}

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public function execute(string $resourceClass): Schema
    {
        return $this->buildResourceSchema($resourceClass);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    private function buildResourceSchema(string $resourceClass): Schema
    {
        // Don't fall into endless loop if a resource is referenced in the parent resource.
        // This same method can be called multiple times to resolve nested resources.
        if (array_key_exists($resourceClass, $this->currentIterationResources)) {
            return Schema::empty();
        }

        $classQuery = ClassQuery::from($resourceClass);

        $this->currentIterationResources[$resourceClass] = $classQuery;

        $returnExpr = $classQuery->method('toArray')?->getReturnExpression();

        if (! $returnExpr instanceof Array_) {
            return Schema::empty();
        }

        $map = $this->parseArrayNode($returnExpr);

        $schema = Schema::fromArrayMap($map);

        unset($this->currentIterationResources[$resourceClass]);

        return $schema;
    }

    /**
     * @return array<string, SchemaPropertyInterface|Schema>
     */
    private function parseArrayNode(Array_ $array, bool $isOptional = false): array
    {
        $shape = [];

        foreach ($array->items as $item) {
            /** @var Node\ArrayItem|null $item */
            if ($item === null) {
                continue;
            }

            // Keys added with `merge()` and `mergeWhen()` have no key of their own.
            if (! $item->key instanceof Node\Scalar\String_) {
                $shape = [...$shape, ...$this->parseMergedKeys($item->value, $isOptional)];

                continue;
            }

            $key = $item->key->value;

            $shape[$key] = $this->resolveKey($item->value, $key, $isOptional);
        }

        return $shape;
    }

    /**
     * @return array<string, SchemaPropertyInterface|Schema>
     */
    private function parseMergedKeys(Node\Expr $expr, bool $isOptional): array
    {
        if (! $this->isCallOnThis($expr, ['merge', 'mergeWhen']) || ! $expr instanceof Node\Expr\MethodCall) {
            return [];
        }

        $isConditional = $expr->name instanceof Node\Identifier && $expr->name->toString() === 'mergeWhen';

        $payload = $expr->args[$isConditional ? 1 : 0]->value ?? null;

        return $payload instanceof Array_
            ? $this->parseArrayNode($payload, $isOptional || $isConditional)
            : [];
    }

    /**
     * Resolves a key, which is optional when the resource adds it conditionally.
     */
    private function resolveKey(Node\Expr $expr, string $key, bool $isOptional): SchemaPropertyInterface|Schema
    {
        $conditionalValue = $this->unwrapConditional($expr);

        if ($conditionalValue === false && ! $isOptional) {
            return $this->resolveItemExpressionType($expr, $key);
        }

        $property = $this->resolveItemExpressionType(
            is_object($conditionalValue) ? $conditionalValue : $expr,
            $key,
        );

        return $property instanceof Schema
            ? new ObjectSchemaProperty(name: $key, required: false, schema: $property)
            : new OptionalSchemaProperty($property);
    }

    /**
     * Returns the value behind a conditional helper such as `when()`, `null` when it has none, or `false` when the expression is not conditional.
     *
     * A resource that wraps a conditional value, such as `new Resource($this->whenLoaded('author'))`, is conditional too.
     */
    private function unwrapConditional(Node\Expr $expr): Node\Expr|false|null
    {
        if ($expr instanceof Node\Expr\MethodCall && $this->isCallOnThis($expr, self::CONDITIONAL_HELPERS)) {
            $name = $expr->name instanceof Node\Identifier ? $expr->name->toString() : '';

            $value = $expr->args[$name === 'whenNotNull' ? 0 : 1]->value ?? null;

            // A closure value runs only when the condition passes, so its body gives the type.
            if ($value instanceof Node\Expr\ArrowFunction) {
                return $value->expr;
            }

            return $value instanceof Node\Expr\Closure ? null : $value;
        }

        $wrappedValue = ($expr instanceof New_ || $expr instanceof StaticCall) ? ($expr->args[0]->value ?? null) : null;

        if ($wrappedValue instanceof Node\Expr && $this->unwrapConditional($wrappedValue) !== false) {
            // The wrapper decides the type, so we keep the whole expression.
            return $expr;
        }

        return false;
    }

    /**
     * @param  string[]  $names
     */
    private function isCallOnThis(Node\Expr $expr, array $names): bool
    {
        return $expr instanceof Node\Expr\MethodCall
            && $expr->var instanceof Node\Expr\Variable
            && $expr->var->name === 'this'
            && $expr->name instanceof Node\Identifier
            && in_array($expr->name->toString(), $names, true);
    }

    private function resolveItemExpressionType(Node\Expr $expr, string $key): SchemaPropertyInterface|Schema
    {
        return $this->attemptResolvingScalarType($expr, $key)
            ?? $this->attemptResolvingNestedResource($expr, $key)
            ?? $this->attemptResolvingModelPropertyType($expr, $key)
            ?? new StringSchemaProperty($key, required: true);
    }

    private function attemptResolvingScalarType(Node\Expr $expr, string $key): ?SchemaPropertyInterface
    {
        $scalarType = $this->getPhpTypeFromAstScalarAction->execute($expr);

        if (! is_string($scalarType)) {
            return null;
        }

        $propertyType = SchemaPropertyType::tryFrom($scalarType);

        return match ($propertyType) {
            SchemaPropertyType::INTEGER => new IntegerSchemaProperty($key, required: true),
            SchemaPropertyType::NUMBER => new NumberSchemaProperty($key, required: true),
            SchemaPropertyType::BOOLEAN => new BooleanSchemaProperty($key, required: true),
            default => new StringSchemaProperty($key, required: true),
        };
    }

    private function attemptResolvingNestedResource(Node\Expr $expr, string $key): ?SchemaPropertyInterface
    {
        $target = $this->resolveExpression($expr);

        if (! $target instanceof NestedResourceTarget) {
            return null;
        }

        $schema = $this->buildResourceSchema($target->resourceClass);

        if ($schema->isEmpty()) {
            return null;
        }

        if ($target->isCollection) {
            return new ArraySchemaProperty(
                name: $key,
                required: true,
                schemaProperty: new ObjectSchemaProperty(name: $key.'_item', required: true, schema: $schema),
            );
        }

        return new ObjectSchemaProperty(name: $key, required: true, schema: $schema);
    }

    private function attemptResolvingModelPropertyType(Node\Expr $expr, string $key): ?SchemaPropertyInterface
    {
        if (! $this->isThisOrResourcePropertyFetch($expr)) {
            return null;
        }

        $modelClass = $this->inferEloquentModelFromJsonResourceAction->execute(
            classQuery: last($this->currentIterationResources),
        );

        if ($modelClass === null) {
            return null;
        }

        return $this->inferSchemaPropertyFromDatabaseColumnAction->execute($modelClass, $key);
    }

    /**
     * Resolves nested JsonResource target from AST expression.
     */
    private function resolveExpression(Node\Expr $expr): ?NestedResourceTarget
    {
        $newTarget = $this->resolveNewInstanceTarget($expr);
        if ($newTarget instanceof NestedResourceTarget) {
            return $newTarget;
        }

        return $this->resolveStaticCallTarget($expr);
    }

    private function resolveNewInstanceTarget(Node\Expr $expr): ?NestedResourceTarget
    {
        if (! ($expr instanceof New_) || ! ($expr->class instanceof Node\Name)) {
            return null;
        }

        $className = $expr->class->toString();

        if (! class_exists($className) || ! is_subclass_of($className, JsonResource::class)) {
            return null;
        }

        return new NestedResourceTarget(resourceClass: $className, isCollection: false);
    }

    private function resolveStaticCallTarget(Node\Expr $expr): ?NestedResourceTarget
    {
        if (! ($expr instanceof StaticCall) || ! ($expr->class instanceof Node\Name) || ! ($expr->name instanceof Node\Identifier)) {
            return null;
        }

        $className = $expr->class->toString();
        $method = $expr->name->toString();

        if (! class_exists($className) || ! is_subclass_of($className, JsonResource::class)) {
            return null;
        }

        return new NestedResourceTarget(
            resourceClass: $className,
            isCollection: $method === 'collection',
        );
    }

    private function isThisOrResourcePropertyFetch(Node\Expr $expr): bool
    {
        if (! $expr instanceof Node\Expr\PropertyFetch) {
            return false;
        }

        // Direct fetch on $this (e.g., $this->something)
        if ($expr->var instanceof Node\Expr\Variable && $expr->var->name === 'this') {
            return true;
        }

        // Nested fetch on $this->resource (e.g., $this->resource->something)
        return $expr->var instanceof Node\Expr\PropertyFetch
            && $expr->var->var instanceof Node\Expr\Variable
            && $expr->var->var->name === 'this'
            && $expr->var->name instanceof Node\Identifier
            && $expr->var->name->toString() === 'resource';
    }
}
