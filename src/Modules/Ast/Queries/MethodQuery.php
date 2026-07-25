<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Queries;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use Sunchayn\Nimbus\Modules\Ast\Actions\GetConcreteValueFromAstExprAction;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;

/**
 * Provides high-level AST queries over a single ClassMethod node.
 */
class MethodQuery
{
    public function __construct(
        public readonly ClassMethod $methodNode,
    ) {}

    public function getName(): string
    {
        return $this->methodNode->name->toString();
    }

    /**
     * Returns the first return statement's expression node if one exists.
     *
     * @todo [ENHANCEMENT] Consider a bulletproof approach for situations where there are many return statements.
     */
    public function getReturnExpression(): ?Expr
    {
        $returnNode = (new NodeFinder)->findFirstInstanceOf($this->methodNode->stmts ?? [], Return_::class);

        return $returnNode?->expr;
    }

    /**
     * Resolves the method's return value to a concrete @see AstContextValueContract value.
     *
     * @param  VariablesContext|null  $context  Pass a custom VariablesContext to override the auto-detected assignments.
     */
    public function getConcreteReturnValue(?VariablesContext $context = null): mixed
    {
        $expr = $this->getReturnExpression();

        if (! $expr instanceof Expr) {
            return null;
        }

        return resolve(GetConcreteValueFromAstExprAction::class)
            ->execute(node: $expr, context: $context ?? $this->getLocalContext())
            ->getValue();
    }

    /**
     * Finds method calls performed on a type-hinted parameter.
     *
     * @param  class-string  $targetType
     * @param  string[]  $methodNames
     * @return MethodCall[]
     */
    public function findMethodCallsOnParameterOfType(string $targetType, array $methodNames): array
    {
        $parameterName = $this->findMethodParameterMatchingType($targetType);

        if ($parameterName === null) {
            return [];
        }

        $allCalls = (new NodeFinder)->findInstanceOf($this->methodNode->stmts ?? [], MethodCall::class);

        return array_values(
            array_filter(
                $allCalls,
                function (MethodCall $methodCall) use ($parameterName, $methodNames): bool {
                    $isMatchingName = $methodCall->name instanceof Node\Identifier
                        && in_array($methodCall->name->toString(), $methodNames);

                    if (! $isMatchingName) {
                        return false;
                    }

                    return $methodCall->var instanceof Variable && $methodCall->var->name === $parameterName;
                }
            ),
        );
    }

    /**
     * Finds static method calls matching specific target class names and method names.
     *
     * @param  string[]  $targetClasses
     * @param  string[]  $methodNames
     * @return Expr\StaticCall[]
     */
    public function findStaticCalls(array $targetClasses, array $methodNames): array
    {
        $allCalls = (new NodeFinder)->findInstanceOf($this->methodNode->stmts ?? [], Expr\StaticCall::class);

        return array_values(
            array_filter(
                $allCalls,
                function (Expr\StaticCall $staticCall) use ($targetClasses, $methodNames): bool {
                    if (! $staticCall->name instanceof Node\Identifier || ! $staticCall->class instanceof Node\Name) {
                        return false;
                    }

                    if (! in_array($staticCall->name->toString(), $methodNames, true)) {
                        return false;
                    }

                    $className = ltrim($staticCall->class->toString(), '\\');

                    return in_array($className, $targetClasses, true)
                        || in_array(class_basename($className), $targetClasses, true);
                }
            )
        );
    }

    /**
     * Finds global function calls matching specific function names.
     *
     * @param  string[]  $functionNames
     * @return Expr\FuncCall[]
     */
    public function findFunctionCalls(array $functionNames): array
    {
        $allCalls = (new NodeFinder)->findInstanceOf($this->methodNode->stmts ?? [], Expr\FuncCall::class);

        return array_values(
            array_filter(
                $allCalls,
                function (Expr\FuncCall $funcCall) use ($functionNames): bool {
                    if (! $funcCall->name instanceof Node\Name) {
                        return false;
                    }

                    $name = ltrim($funcCall->name->toString(), '\\');

                    return in_array($name, $functionNames, true);
                }
            )
        );
    }

    /**
     * Gathers all local variable assignments defined in the method body into a VariablesContext.
     */
    public function getLocalContext(): VariablesContext
    {
        $assignments = (new NodeFinder)->findInstanceOf($this->methodNode->stmts ?? [], Assign::class);

        $context = VariablesContext::empty();

        foreach ($assignments as $assignment) {
            // Skip method calls, static calls, and new instance assignments to avoid side effects or unresolvable AST expressions.
            if (
                $assignment->expr instanceof MethodCall ||
                $assignment->expr instanceof Node\Expr\StaticCall ||
                $assignment->expr instanceof Node\Expr\New_
            ) {
                continue;
            }

            if (! ($assignment->var instanceof Variable) || ! is_string($assignment->var->name)) {
                continue;
            }

            $varName = $assignment->var->name;

            $variable = resolve(GetConcreteValueFromAstExprAction::class)
                ->execute(node: $assignment->expr, context: $context, variableName: $varName);

            $context = $context->add($variable);
        }

        return $context;
    }

    /**
     * Finds the name of the first parameter that matches or extends $targetType.
     *
     * @param  class-string  $targetType
     */
    public function findMethodParameterMatchingType(string $targetType): ?string
    {
        foreach ($this->methodNode->params as $param) {
            if (! $param->type instanceof Node\Name) {
                continue;
            }

            // The AST has classes with FQN strings with a leading backslash (e.g. \Illuminate\Http\Request).
            // We strip it so class_exists() and is_subclass_of() comparisons work correctly.
            $type = ltrim($param->type->toString(), '\\');

            if ($type !== $targetType && ! is_subclass_of($type, $targetType)) {
                continue;
            }

            if ($param->var instanceof Variable && is_string($param->var->name)) {
                return $param->var->name;
            }
        }

        return null;
    }

    /**
     * Resolves the method's declared object return typehint from the AST if one exists.
     *
     * Returns null for union types, intersection types, or unannotated methods.
     *
     * @return class-string|null
     */
    public function getObjectTypeHintReturnType(): ?string
    {
        $type = $this->methodNode->returnType;

        if ($type instanceof Node\Name) {
            // The AST has classes with FQN strings with a leading backslash (e.g., \Illuminate\Http\Request).
            // We strip it so class_exists() and is_subclass_of() comparisons work correctly.
            // @phpstan-ignore-next-line it is a class-string
            return ltrim($type->toString(), '\\');
        }

        return null;
    }
}
