<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response\Strategies;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeFinder;
use ReflectionClass;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;
use Sunchayn\Nimbus\Modules\Extractor\Actions\InferSchemaFromResourceAstAction;
use Sunchayn\Nimbus\Modules\Extractor\Services\Response\Contracts\ResponseSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Schemas\Contracts\SchemaPropertyInterface;
use Sunchayn\Nimbus\Modules\Schemas\Enums\StringFormat;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ArraySchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\BooleanSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\IntegerSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;

/**
 * Extracts a response Schema from controllers that return a resource collection of a paginator.
 *
 * Handles `Resource::collection(Model::paginate())` and `new ResourceCollection($paginator)`
 * with `paginate`, `simplePaginate`, and `cursorPaginate`, and wraps the items in the
 * `data`, `links`, and `meta` envelope that Laravel adds.
 */
class PaginatedResourceCollectionStrategy implements ResponseSchemaStrategyContract
{
    private const PAGINATORS = ['paginate', 'simplePaginate', 'cursorPaginate'];

    public function __construct(
        private readonly InferSchemaFromResourceAstAction $inferSchemaFromResourceAstAction,
    ) {}

    public function attempt(Route $route): ?Schema
    {
        /** @var class-string|null $controllerClass */
        $controllerClass = $route->getControllerClass();

        $methodName = $route->getActionMethod();

        if (empty($controllerClass) || empty($methodName)) {
            return null;
        }

        $methodQuery = ClassQuery::from($controllerClass)->method($methodName);

        $returnExpr = $methodQuery?->getReturnExpression();

        if (! $methodQuery instanceof MethodQuery || ! $returnExpr instanceof Expr) {
            return null;
        }

        $target = $this->findCollectionTarget($returnExpr);

        if ($target === null) {
            return null;
        }

        [$resourceClass, $source] = $target;

        $paginator = $this->findPaginator($source, $methodQuery);

        if ($paginator === null) {
            return null;
        }

        $itemSchema = $this->inferSchemaFromResourceAstAction->execute($resourceClass);

        return $itemSchema->isEmpty() ? null : $this->buildEnvelope($itemSchema, $paginator);
    }

    /**
     * Finds the resource class and the expression that feeds the collection.
     *
     * @return array{class-string<JsonResource>, Expr}|null
     */
    private function findCollectionTarget(Expr $expr): ?array
    {
        // Calls chained after the collection, such as ->additional(), do not change its shape.
        while ($expr instanceof MethodCall) {
            $expr = $expr->var;
        }

        $source = ($expr instanceof New_ || $expr instanceof StaticCall) ? ($expr->args[0]->value ?? null) : null;

        if (! $source instanceof Expr) {
            return null;
        }

        if ($expr instanceof StaticCall && $expr->class instanceof Node\Name && $expr->name instanceof Node\Identifier && $expr->name->toString() === 'collection') {
            $class = $expr->class->toString();

            return $this->isResource($class) ? [$class, $source] : null;
        }

        if ($expr instanceof New_ && $expr->class instanceof Node\Name) {
            $collection = $expr->class->toString();

            $resource = $this->resolveCollectedResource($collection);

            return $resource === null ? null : [$resource, $source];
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true class-string<JsonResource> $class
     */
    private function isResource(string $class): bool
    {
        return class_exists($class) && is_subclass_of($class, JsonResource::class);
    }

    /**
     * Mirrors how Laravel finds the resource of a collection class, without creating it.
     *
     * @return class-string<JsonResource>|null
     */
    private function resolveCollectedResource(string $collection): ?string
    {
        if (! class_exists($collection) || ! is_subclass_of($collection, ResourceCollection::class)) {
            return null;
        }

        $collects = (new ReflectionClass($collection))->getDefaultProperties()['collects'] ?? null;

        $candidates = [
            is_string($collects) ? $collects : null,
            Str::replaceLast('Collection', '', $collection),
            Str::replaceLast('Collection', '', $collection).'Resource',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== $collection && $this->isResource($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Finds which paginator feeds the collection, looking through a variable assigned in the method.
     */
    private function findPaginator(Expr $source, MethodQuery $methodQuery): ?string
    {
        $nodeFinder = new NodeFinder;

        $roots = [$source];

        if ($source instanceof Variable && is_string($source->name)) {
            $name = $source->name;

            $assignments = $nodeFinder->find(
                $methodQuery->methodNode->stmts ?? [],
                fn (Node $node): bool => $node instanceof Assign && $node->var instanceof Variable && $node->var->name === $name,
            );

            $roots = [];

            foreach ($assignments as $assignment) {
                if ($assignment instanceof Assign) {
                    $roots[] = $assignment->expr;
                }
            }
        }

        foreach ($roots as $root) {
            /** @var MethodCall|null $call */
            $call = $nodeFinder->findFirst(
                $root,
                fn (Node $node): bool => $node instanceof MethodCall
                    && $node->name instanceof Node\Identifier
                    && in_array($node->name->toString(), self::PAGINATORS, true),
            );

            if ($call !== null) {
                /** @var Node\Identifier $name */
                $name = $call->name;

                return $name->toString();
            }
        }

        return null;
    }

    private function buildEnvelope(Schema $itemSchema, string $paginator): Schema
    {
        return new Schema([
            new ArraySchemaProperty(
                name: 'data',
                required: true,
                schemaProperty: new ObjectSchemaProperty(name: 'data_item', required: true, schema: $itemSchema),
            ),
            new ObjectSchemaProperty(name: 'links', required: true, schema: new Schema([
                $this->nullableLink('first'),
                $this->nullableLink('last'),
                $this->nullableLink('prev'),
                $this->nullableLink('next'),
            ])),
            new ObjectSchemaProperty(name: 'meta', required: true, schema: new Schema($this->metaProperties($paginator))),
        ]);
    }

    /**
     * @return SchemaPropertyInterface[]
     */
    private function metaProperties(string $paginator): array
    {
        $stringSchemaProperty = new StringSchemaProperty(name: 'path', required: true);
        $integerSchemaProperty = new IntegerSchemaProperty(name: 'per_page', required: true);

        return match ($paginator) {
            'cursorPaginate' => [
                $stringSchemaProperty,
                $integerSchemaProperty,
                new StringSchemaProperty(name: 'next_cursor', required: true, nullable: true),
                new StringSchemaProperty(name: 'prev_cursor', required: true, nullable: true),
            ],
            'simplePaginate' => [
                new IntegerSchemaProperty(name: 'current_page', required: true),
                new IntegerSchemaProperty(name: 'from', required: true, nullable: true),
                $stringSchemaProperty,
                $integerSchemaProperty,
                new IntegerSchemaProperty(name: 'to', required: true, nullable: true),
            ],
            default => [
                new IntegerSchemaProperty(name: 'current_page', required: true),
                new IntegerSchemaProperty(name: 'from', required: true, nullable: true),
                new IntegerSchemaProperty(name: 'last_page', required: true),
                new ArraySchemaProperty(
                    name: 'links',
                    required: true,
                    schemaProperty: new ObjectSchemaProperty(name: 'link', required: true, schema: new Schema([
                        new StringSchemaProperty(name: 'url', required: true, nullable: true),
                        new StringSchemaProperty(name: 'label', required: true),
                        new BooleanSchemaProperty(name: 'active', required: true),
                    ])),
                ),
                $stringSchemaProperty,
                $integerSchemaProperty,
                new IntegerSchemaProperty(name: 'to', required: true, nullable: true),
                new IntegerSchemaProperty(name: 'total', required: true),
            ],
        };
    }

    private function nullableLink(string $name): StringSchemaProperty
    {
        return new StringSchemaProperty(name: $name, required: true, nullable: true, stringFormat: StringFormat::URI);
    }
}
