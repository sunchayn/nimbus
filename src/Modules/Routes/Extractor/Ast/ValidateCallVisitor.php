<?php

namespace Sunchayn\Nimbus\Modules\Routes\Extractor\Ast;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\Shared\QualifiesTypehint;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;

/**
 * Extracts validation rules from controller methods using AST analysis.
 *
 * Finds request validation calls (validate, validateWithBag) within controller
 * action methods and extracts their validation rule arguments.
 *
 * @example Controller: $request->validate(['name' => 'required|string'])
 * @example Output: ['name' => 'required|string']
 */
class ValidateCallVisitor extends NodeVisitorAbstract
{
    use QualifiesTypehint;

    private const SUPPORTED_VALIDATION_METHODS = ['validate', 'validateWithBag'];

    private string $targetControllerMethod;

    /** @var array<string, Node\Stmt\ClassMethod> */
    private array $classMethodNodes = [];

    private ?Ruleset $rules = null;

    /** @var array<string, mixed> Variables defined within the method */
    private array $context = [];

    public function __construct(string $routeActionMethod)
    {
        $this->targetControllerMethod = $routeActionMethod;
    }

    public function beforeTraverse(array $nodes): array
    {
        $this->gatherClassMethods($nodes);

        return $this->qualifyClassTypeHinting($nodes);
    }

    public function getRules(): Ruleset
    {
        return $this->rules ?? Ruleset::fromLaravelRules([]);
    }

    public function enterNode(Node $node): null|int|Node|array
    {
        if (! $this->isTargetControllerMethod($node)) {
            return null;
        }

        /** @var Node\Stmt\ClassMethod $node */
        $this->extractValidationRules($node);

        return NodeVisitor::STOP_TRAVERSAL;
    }

    private function isTargetControllerMethod(Node $node): bool
    {
        return $node instanceof Node\Stmt\ClassMethod
            && $node->name->toString() === $this->targetControllerMethod;
    }

    private function extractValidationRules(Node\Stmt\ClassMethod $classMethod): void
    {
        // TODO [Enhancement] Support validation rules from wrapped method calls (e.g., $this->validateFormData(..)).

        if ($classMethod->stmts === null) {
            return;
        }

        foreach ($classMethod->stmts as $statement) {
            if (! ($statement instanceof Expression)) {
                continue;
            }

            if ($statement->expr instanceof Assign) {
                $this->storeVariableAssignment($statement->expr);
            }

            $expression = $this->extractExpressionFromNode($statement);

            if ($this->isEligibleMethodCall($expression, $classMethod)) {
                /** @var MethodCall $expression */
                $this->processValidationCall($expression);
            }
        }
    }

    private function extractExpressionFromNode(Expression $expression): Node\Expr
    {
        return match (true) {
            $expression->expr instanceof Assign => $expression->expr->expr,
            default => $expression->expr,
        };
    }

    private function isEligibleMethodCall(
        Node\Expr $expr,
        Node\Stmt\ClassMethod $classMethod
    ): bool {
        if (! ($expr instanceof MethodCall)) {
            return false;
        }

        if (! ($expr->name instanceof Identifier)) {
            return false;
        }

        if (! in_array($expr->name->toString(), self::SUPPORTED_VALIDATION_METHODS, true)) {
            return false;
        }

        return $this->isCalledOnRequestInstance($expr, $classMethod);
    }

    private function isCalledOnRequestInstance(
        MethodCall $methodCall,
        Node\Stmt\ClassMethod $classMethod
    ): bool {
        if (! ($methodCall->var instanceof Variable) || ! is_string($methodCall->var->name)) {
            return true; // <- Assume it's valid if we can't determine the variable.
        }

        $varName = $methodCall->var->name;

        $matchingParameter = Arr::first(
            $classMethod->params,
            fn (Node\Param $param): bool => $param->var instanceof Variable && $param->var->name === $varName,
        );

        if ($matchingParameter === null) {
            return false;
        }

        $type = $matchingParameter->type->name ?? null;

        return $type === Request::class || is_subclass_of($type, Request::class);
    }

    private function processValidationCall(MethodCall $methodCall): void
    {
        $methodName = $methodCall->name instanceof Node\Identifier
            ? $methodCall->name->toString()
            : null;

        $argNode = $this->extractValidationRulesArgument($methodCall, $methodName);

        if (! $argNode instanceof \PhpParser\Node) {
            return;
        }

        if ($this->isNestedMethodCall($argNode)) {
            /** @var Identifier $argIdentifier */
            $argIdentifier = $argNode->name;

            $this->processNestedMethodCall(methodName: $argIdentifier->name);

            return;
        }

        $this->rules = Ruleset::fromLaravelRules(
            ConvertNodeToConcreteValue::process($argNode, $this->context)
        );
    }

    private function extractValidationRulesArgument(MethodCall $methodCall, ?string $methodName): ?Node
    {
        return match ($methodName) {
            'validate' => $methodCall->args[0]->value ?? null,
            'validateWithBag' => $methodCall->args[1]->value ?? null,
            default => null,
        };
    }

    /**
     * @phpstan-assert-if-true MethodCall $argNode
     */
    private function isNestedMethodCall(Node $argNode): bool
    {
        if (! ($argNode instanceof MethodCall)) {
            return false;
        }

        if (! ($argNode->name instanceof Identifier)) {
            return false;
        }

        return array_key_exists($argNode->name->name, $this->classMethodNodes);
    }

    private function processNestedMethodCall(string $methodName): void
    {
        $nestedMethod = $this->classMethodNodes[$methodName];

        $this->extractRulesFromReturnStatement($nestedMethod);
    }

    private function extractRulesFromReturnStatement(Node\Stmt\ClassMethod $classMethod): void
    {
        /** @var Node\Stmt\Return_|null $returnStatement */
        $returnStatement = Arr::first(
            $classMethod->stmts ?? [],
            fn ($stmt): bool => $stmt instanceof Node\Stmt\Return_,
        );

        if ($returnStatement === null || ! $this->isArrayReturn($returnStatement)) {
            return;
        }

        // TODO [Enhancement] Account for nested method calls in return statements

        $this->addMethodVariablesToContext($classMethod);

        if (! ($returnStatement->expr instanceof Node)) {
            return;
        }

        $this->rules = Ruleset::fromLaravelRules(
            ConvertNodeToConcreteValue::process($returnStatement->expr, $this->context)
        );
    }

    private function isArrayReturn(Node\Stmt\Return_ $return): bool
    {
        return $return->expr instanceof Node\Expr\Array_;
    }

    private function addMethodVariablesToContext(Node\Stmt\ClassMethod $classMethod): void
    {
        if ($classMethod->stmts === null) {
            return;
        }

        foreach ($classMethod->stmts as $statement) {
            if (! property_exists($statement, 'expr')) {
                continue;
            }

            if (! ($statement->expr instanceof Assign)) {
                continue;
            }

            $this->storeVariableAssignment($statement->expr);
        }
    }

    private function storeVariableAssignment(Assign $assign): void
    {
        if ($assign->expr instanceof MethodCall) {
            return;
        }

        if (! ($assign->var instanceof Variable) || ! is_string($assign->var->name)) {
            return;
        }

        $this->context[$assign->var->name] = ConvertNodeToConcreteValue::process(
            $assign->expr,
            $this->context
        );
    }

    /**
     * Find all nodes that are a class method node.
     *
     * @param  Node[]  $nodes
     */
    private function gatherClassMethods(array $nodes): void
    {
        /** @var Node\Stmt\Class_|null $classNode */
        $classNode = Arr::first(
            $nodes,
            fn (Node $node): bool => $node instanceof Node\Stmt\Class_
        );

        if ($classNode === null) {
            return;
        }

        foreach ($classNode->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $this->classMethodNodes[$stmt->name->name] = $stmt;
            }
        }
    }
}
