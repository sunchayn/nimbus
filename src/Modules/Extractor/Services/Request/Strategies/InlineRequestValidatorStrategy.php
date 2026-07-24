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
use Sunchayn\Nimbus\Modules\Extractor\Services\Request\Contracts\RequestSchemaStrategyContract;
use Sunchayn\Nimbus\Modules\Schemas\Builders\SchemaBuilder;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;
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
        $expressions = $routeActionMethod->findMethodCallsOnParameterOfType(
            targetType: Request::class,
            methodNames: ['validate', 'validateWithBag']
        );

        $rulesArray = $this->extractRules(
            expressions: $expressions,
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
     * Extracts rules from all validation calls in the controller method.
     *
     * @param  MethodCall[]  $expressions
     * @return array<string, mixed>
     */
    private function extractRules(array $expressions, ClassQuery $classQuery, MethodQuery $methodQuery): array
    {
        $rules = [];

        foreach ($expressions as $expression) {
            $rules = array_merge(
                $rules,
                $this->resolveRulesFromCall($classQuery, $methodQuery, $expression)
            );
        }

        return $rules;
    }

    /**
     * Resolves the rules' array from a specific `validate` or `validateWithBag` call.
     *
     * @return array<string, mixed>
     */
    private function resolveRulesFromCall(
        ClassQuery $classQuery,
        MethodQuery $methodQuery,
        MethodCall $methodCall,
    ): array {
        $node = $this->getRulesNodeFromMethodCall($methodCall);

        if (! $node instanceof Node) {
            return [];
        }

        $variablesContext = $methodQuery->getLocalContext();

        // If rules are defined in a separate method (e.g. $this->rules()), resolve
        // that method's return value using its own local context.
        // Otherwise, evaluate the inline node using this method's local assignments.
        $resolved = $node instanceof MethodCall && $node->name instanceof Identifier
            ? $classQuery->method($node->name->toString())?->getConcreteReturnValue($variablesContext)
            : $this->getConcreteValueFromAstExprAction->execute($node, $variablesContext)->getValue();

        return is_array($resolved) ? $resolved : [];
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
