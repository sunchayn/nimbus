<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Actions;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use ReflectionException;
use ReflectionMethod;
use Sunchayn\Nimbus\Modules\Ast\Contracts\AstContextValueContract;
use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
use Sunchayn\Nimbus\Modules\Ast\Queries\MethodQuery;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ArrayAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ScalarAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;

/**
 * Evaluates an AST expression into an @see AstContextValueContract object.
 *
 * @todo [ENHANCEMENT] fix the variableName mess, perhaps using a pendingObject pattern to set the variable name once at the end.
 *
 * @final
 */
class GetConcreteValueFromAstExprAction
{
    private VariablesContext $variablesContext;

    private ?string $variableName;

    private ?ClassQuery $classQuery;

    public function execute(
        Node $node,
        ?VariablesContext $context = null,
        ?string $variableName = null,
        ?ClassQuery $classQuery = null,
    ): AstContextValueContract {
        $this->variablesContext = $context ?? VariablesContext::empty();

        $this->variableName = $variableName;

        $this->classQuery = $classQuery;

        return $this->process($node);
    }

    private function process(Node $node): AstContextValueContract
    {
        return match (true) {
            $node instanceof Node\Scalar\String_ => new ScalarAstContextValue(value: $node->value, variableName: $this->variableName),
            $node instanceof Node\Scalar\Int_ => new ScalarAstContextValue(value: $node->value, variableName: $this->variableName),
            $node instanceof Node\Scalar\Float_ => new ScalarAstContextValue(value: $node->value, variableName: $this->variableName),
            $node instanceof Node\Expr\Array_ => new ArrayAstContextValue(value: $this->resolveArray($node), variableName: $this->variableName),
            $node instanceof Node\Expr\Variable => $this->resolveVariable($node),
            $node instanceof Expr\MethodCall => $this->resolveMethodCall($node),
            $node instanceof Node\Expr\StaticCall => $this->resolveStaticCall($node),
            $node instanceof Expr\New_ => $this->resolveNewInstance($node),
            $node instanceof Node\Expr\ConstFetch => new ScalarAstContextValue(value: $this->resolveConstant($node), variableName: $this->variableName),
            $node instanceof Node\Expr\BinaryOp\Concat => new ScalarAstContextValue(value: $this->resolveConcatenation($node), variableName: $this->variableName),
            $node instanceof Node\Scalar\InterpolatedString => new ScalarAstContextValue(value: $this->resolveInterpolatedString($node), variableName: $this->variableName),
            $node instanceof Node\InterpolatedStringPart => new ScalarAstContextValue(value: $node->value, variableName: $this->variableName),
            $node instanceof Expr\ClassConstFetch && $node->class instanceof Name => new ScalarAstContextValue(value: $node->class->name, variableName: $this->variableName),
            default => new ScalarAstContextValue(value: null, variableName: $this->variableName),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    private function resolveArray(Node\Expr\Array_ $array): array
    {
        $result = [];

        foreach ($array->items as $item) {
            $value = $this->process($item->value)->getValue();

            if ($item->key === null) {
                $result[] = $value;

                continue;
            }

            $key = $this->process($item->key)->getValue();

            if (is_string($key) || is_int($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function resolveVariable(Node\Expr\Variable $variable): AstContextValueContract
    {
        $varName = $variable->name;

        if ($varName instanceof Expr\Variable && is_string($varName->name)) {
            $varName = $this->variablesContext->get($varName->name)?->getValue();
        }

        if (! is_string($varName)) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        return $this->variablesContext->get($varName) ?? new ScalarAstContextValue(value: null, variableName: $this->variableName);
    }

    private function resolveStaticCall(Node\Expr\StaticCall $staticCall): AstContextValueContract
    {
        $arguments = array_filter(
            array_map(
                function (Arg|VariadicPlaceholder $argNode): mixed {
                    if ($argNode instanceof VariadicPlaceholder) {
                        return null;
                    }

                    return $this->process($argNode->value)->getValue();
                },
                $staticCall->args,
            ),
            fn ($arg): bool => $arg !== null
        );

        if (count($arguments) !== count($staticCall->args)) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        if (! ($staticCall->name instanceof Identifier) || ! ($staticCall->class instanceof Name)) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        $val = $staticCall->class->name::{$staticCall->name->name}(...$arguments);

        if (is_scalar($val) || $val === null) {
            return new ScalarAstContextValue(value: $val, variableName: $this->variableName);
        }

        if (is_array($val)) {
            return new ArrayAstContextValue(value: $val, variableName: $this->variableName);
        }

        if (is_object($val)) {
            return new ObjectAstContextValue(className: get_class($val), variableName: $this->variableName);
        }

        return new ScalarAstContextValue(value: null, variableName: $this->variableName);
    }

    private function resolveNewInstance(Expr\New_ $new): AstContextValueContract
    {
        if (! ($new->class instanceof Name)) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        return new ObjectAstContextValue(className: $new->class->toString(), variableName: $this->variableName);
    }

    private function resolveConstant(Node\Expr\ConstFetch $constFetch): mixed
    {
        return constant($constFetch->name->toString());
    }

    private function resolveConcatenation(Node\Expr\BinaryOp\Concat $concat): string
    {
        return $this->process($concat->left)->getValue().$this->process($concat->right)->getValue();
    }

    private function resolveInterpolatedString(Node\Scalar\InterpolatedString $interpolatedString): string
    {
        return array_reduce(
            $interpolatedString->parts,
            fn ($carry, Node $current): string => $carry.$this->process($current)->getValue(),
            initial: '',
        );
    }

    private function resolveMethodCall(Expr\MethodCall $methodCall): AstContextValueContract
    {
        if (! $this->classQuery instanceof ClassQuery) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        $isThis = $methodCall->var instanceof Expr\Variable && $methodCall->var->name === 'this';

        if (! $isThis || ! ($methodCall->name instanceof Identifier)) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        $methodName = $methodCall->name->toString();

        $targetClassQuery = $this->resolveDeclaringClassQuery($this->classQuery, $methodName);

        $methodQuery = $targetClassQuery->method($methodName);

        if (! $methodQuery instanceof MethodQuery) {
            return new ScalarAstContextValue(value: null, variableName: $this->variableName);
        }

        $val = $methodQuery->getConcreteReturnValue($this->variablesContext);

        return match (true) {
            is_array($val) => new ArrayAstContextValue(value: $val, variableName: $this->variableName),
            is_scalar($val) || $val === null => new ScalarAstContextValue(value: $val, variableName: $this->variableName),
            default => new ScalarAstContextValue(value: null, variableName: $this->variableName),
        };
    }

    /**
     * Resolves the ClassQuery of the class or trait where a method is declared.
     */
    private function resolveDeclaringClassQuery(ClassQuery $classQuery, string $methodName): ClassQuery
    {
        $className = $classQuery->className();

        if (! class_exists($className) && ! trait_exists($className)) {
            return $classQuery;
        }

        try {
            $reflectionClass = new \ReflectionClass($className);

            foreach ($reflectionClass->getTraits() as $trait) {
                if ($trait->hasMethod($methodName)) {
                    return ClassQuery::from($trait->getName());
                }
            }

            $declaringClass = (new ReflectionMethod($className, $methodName))->getDeclaringClass()->getName();

            if ($declaringClass !== $className) {
                return ClassQuery::from($declaringClass);
            }

            return $classQuery;
        } catch (ReflectionException) {
            return $classQuery;
        }
    }
}
