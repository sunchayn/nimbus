<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetConcreteValueFromAstExprAction;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts\RequestSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
use Sunchayn\Nimbus\Modules\Schemas\Services\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;

/**
 * Extracts validation rules from inline $request->validate() calls in controller methods.
 */
class InlineRequestValidatorStrategy implements RequestSchemaStrategyContract
{
    public function __construct(
        private readonly SchemaBuilder $schemaBuilder,
        private readonly GetConcreteValueFromAstExprAction $getConcreteValueFromAstExprAction,
    ) {}

    public function attempt(Route $route): ?Schema
    {
        $controllerClass = $route->getControllerClass();
        $methodName = $route->getActionMethod();

        if (empty($controllerClass) || empty($methodName)) {
            return null;
        }

        /** @var class-string $controllerClass */
        $classQuery = ClassQuery::from($controllerClass);

        $methodQuery = $classQuery->method($methodName);

        if (! $methodQuery instanceof MethodQuery) {
            return Schema::empty();
        }

        return $this->extractSchemaFromMethod($classQuery, $methodQuery);
    }

    private function extractSchemaFromMethod(ClassQuery $controllerClassQuery, MethodQuery $routeActionMethod): ?Schema
    {
        $nodes = $this->collectValidationNodes($routeActionMethod);

        $rulesArray = $this->extractRulesFromNodes(
            nodes: $nodes,
            classQuery: $controllerClassQuery,
            methodQuery: $routeActionMethod,
        );

        if ($rulesArray === []) {
            return null;
        }

        $schema = $this->schemaBuilder->buildSchemaFromRuleset(Ruleset::fromLaravelRules($rulesArray));

        return $schema->isEmpty() ? null : $schema;
    }

    /**
     * Collects AST nodes containing validation rules from method calls, static calls, and helpers.
     *
     * @return Node[]
     */
    private function collectValidationNodes(MethodQuery $routeActionMethod): array
    {
        return array_merge(
            $this->collectRequestCallNodes($routeActionMethod),
            $this->collectValidatorStaticNodes($routeActionMethod),
            $this->collectValidatorHelperNodes($routeActionMethod),
        );
    }

    /**
     * @return Node[]
     */
    private function collectRequestCallNodes(MethodQuery $routeActionMethod): array
    {
        $calls = $routeActionMethod->findMethodCallsOnParameterOfType(
            targetType: Request::class,
            methodNames: ['validate', 'validateWithBag'],
        );

        return array_values(
            array_filter(
                array_map(fn (MethodCall $call): ?Node => $this->getRulesNodeFromMethodCall($call), $calls)
            )
        );
    }

    /**
     * @return Node[]
     */
    private function collectValidatorStaticNodes(MethodQuery $routeActionMethod): array
    {
        $calls = $routeActionMethod->findStaticCalls(
            targetClasses: ['Validator', \Illuminate\Support\Facades\Validator::class],
            methodNames: ['make'],
        );

        return array_values(
            array_filter(
                array_map(fn (Node\Expr\StaticCall $call): ?Node => $call->args[1]->value ?? null, $calls)
            )
        );
    }

    /**
     * @return Node[]
     */
    private function collectValidatorHelperNodes(MethodQuery $routeActionMethod): array
    {
        $calls = $routeActionMethod->findFunctionCalls(['validator']);

        return array_values(
            array_filter(
                array_map(
                    fn (Node\Expr\FuncCall $call): ?Node => count($call->args) >= 2 ? ($call->args[1]->value ?? null) : null,
                    $calls
                )
            )
        );
    }

    /**
     * Extracts rules from all validation nodes in the controller method.
     *
     * @param  Node[]  $nodes
     * @return array<string, mixed>
     */
    private function extractRulesFromNodes(array $nodes, ClassQuery $classQuery, MethodQuery $methodQuery): array
    {
        $rules = [];

        foreach ($nodes as $node) {
            $rules = array_merge(
                $rules,
                $this->resolveRulesFromNode($node, $classQuery, $methodQuery)
            );
        }

        return $rules;
    }

    /**
     * Resolves the rules' array from a validation rules AST node.
     *
     * @return array<string, mixed>
     */
    private function resolveRulesFromNode(
        Node $node,
        ClassQuery $classQuery,
        MethodQuery $methodQuery,
    ): array {
        $variablesContext = $methodQuery->getLocalContext();

        $resolved = match (true) {
            // If rules are defined in a separate method (e.g. $this->rules()),
            // resolve that method's return value using its own local context.
            $node instanceof MethodCall && $node->name instanceof Identifier => $classQuery->method($node->name->toString())?->getConcreteReturnValue($variablesContext),

            // Also, consider static calls (e.g. self::rules())
            $node instanceof Node\Expr\StaticCall && $node->name instanceof Identifier => $this->resolveStaticRulesCall($node, $classQuery, $variablesContext),

            // Otherwise, evaluate the inline node using this method's local assignments.
            default => $this->getConcreteValueFromAstExprAction->execute($node, $variablesContext, classQuery: $classQuery)->getValue(),
        };

        return is_array($resolved) ? $resolved : [];
    }

    private function resolveStaticRulesCall(
        Node\Expr\StaticCall $staticCall,
        ClassQuery $classQuery,
        VariablesContext $context,
    ): mixed {
        if (! $staticCall->class instanceof Node\Name || ! $staticCall->name instanceof Identifier) {
            return null;
        }

        $calleeClass = $staticCall->class->toString();

        $targetMethod = $staticCall->name->toString();

        $isCurrentClass = in_array(
            needle: $calleeClass,
            haystack: [
                'self',
                'static',
                $classQuery->className(),
                $classQuery->classShortName(),
            ],
            strict: true,
        );

        if (! $isCurrentClass) {
            return null;
        }

        return $classQuery->method($targetMethod)?->getConcreteReturnValue($context);
    }

    /**
     * Identifies the node containing the rules' array argument.
     */
    private function getRulesNodeFromMethodCall(MethodCall $methodCall): ?Node
    {
        /** @var Identifier $name */
        $name = $methodCall->name;

        $methodName = $name->toString();

        return match ($methodName) {
            'validate' => $methodCall->args[0]->value ?? null,
            'validateWithBag' => $methodCall->args[1]->value ?? null,
            default => null,
        };
    }
}
