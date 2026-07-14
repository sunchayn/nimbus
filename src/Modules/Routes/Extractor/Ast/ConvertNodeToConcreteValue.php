<?php

namespace Sunchayn\Nimbus\Modules\Routes\Extractor\Ast;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;

/**
 * Converts AST nodes into their concrete PHP values.
 *
 * @example Node\Scalar\LNumber(2) -> 2
 * @example Node\Expr\Array_([2, 'example']) -> [2, 'example']
 * @example Node\Expr\New_(SomeClass) -> new SomeClass()
 */
class ConvertNodeToConcreteValue
{
    /**
     * @param  array<string, mixed>  $variablesContext
     */
    public static function process(Node $node, array $variablesContext = []): mixed
    {
        return match (true) {
            $node instanceof Node\Scalar\String_ => $node->value,
            $node instanceof Node\Scalar\LNumber => $node->value,
            $node instanceof Node\Scalar\DNumber => $node->value,
            $node instanceof Node\Expr\Array_ => self::resolveArray($node, $variablesContext),
            $node instanceof Node\Expr\Variable => self::resolveVariable($node, $variablesContext),
            $node instanceof Node\Expr\StaticCall => self::resolveStaticCall($node, $variablesContext),
            $node instanceof Expr\New_ => self::resolveNewInstance($node, $variablesContext),
            $node instanceof Node\Expr\ConstFetch => self::resolveConstant($node),
            $node instanceof Node\Expr\BinaryOp\Concat => self::resolveConcatenation($node, $variablesContext),
            $node instanceof Node\Scalar\InterpolatedString => self::resolveInterpolatedString($node, $variablesContext),
            $node instanceof Node\InterpolatedStringPart => $node->value,
            $node instanceof Expr\ClassConstFetch && $node->class instanceof Name => $node->class->name,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $variablesContext
     * @return array<array-key, mixed>
     */
    private static function resolveArray(Node\Expr\Array_ $array, array $variablesContext): array
    {
        $result = [];

        foreach ($array->items as $item) {
            $value = self::process($item->value, $variablesContext);

            if ($item->key === null) {
                $result[] = $value;

                continue;
            }

            $key = self::process($item->key, $variablesContext);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $variablesContext
     */
    private static function resolveVariable(Node\Expr\Variable $variable, array $variablesContext): mixed
    {
        $varName = $variable->name;

        if (! is_string($varName)) {
            return null;
        }

        return $variablesContext[$varName] ?? null;
    }

    /**
     * @param  array<string, mixed>  $variablesContext
     */
    private static function resolveStaticCall(Node\Expr\StaticCall $staticCall, array $variablesContext): mixed
    {
        $arguments = array_filter( // <- TODO [Test] make sure to assert this filter if not already.
            array_map(
                function (Arg|VariadicPlaceholder $argNode) use ($variablesContext): mixed {
                    if ($argNode instanceof VariadicPlaceholder) {
                        return null;
                    }

                    return self::process($argNode->value, $variablesContext);
                },
                $staticCall->args,
            ),
        );

        // If we failed to resolve all arguments, return null to avoid incomplete values
        if (count($arguments) !== count($staticCall->args)) {
            return null;
        }

        if (! ($staticCall->name instanceof Identifier)) {
            return null;
        }

        if (! ($staticCall->class instanceof Name)) {
            return null;
        }

        return $staticCall->class->name::{$staticCall->name->name}(...$arguments);
    }

    /**
     * @param  array<string, mixed>  $variablesContext
     */
    private static function resolveNewInstance(Expr\New_ $new, array $variablesContext): ?object
    {
        $arguments = array_filter( // <- TODO [Test] make sure to assert this filter if not already.
            array_map(
                function (Arg|VariadicPlaceholder $argNode) use ($variablesContext): mixed {
                    if ($argNode instanceof VariadicPlaceholder) {
                        return null;
                    }

                    return self::process($argNode->value, $variablesContext);
                },
                $new->args,
            ),
        );

        // If we failed to resolve all arguments, return null to avoid incomplete values
        if (count($arguments) !== count($new->args)) {
            return null;
        }

        if (! ($new->class instanceof Name)) {
            return null;
        }

        return new $new->class->name(...$arguments);
    }

    private static function resolveConstant(Node\Expr\ConstFetch $constFetch): mixed
    {
        return constant($constFetch->name->toString());
    }

    /**
     * @param  array<string, mixed>  $variablesContext
     */
    private static function resolveConcatenation(Node\Expr\BinaryOp\Concat $concat, array $variablesContext): string
    {
        return self::process($concat->left, $variablesContext).self::process($concat->right, $variablesContext);
    }

    /**
     * @param  array<string, mixed>  $variablesContext
     */
    private static function resolveInterpolatedString(Node\Scalar\InterpolatedString $interpolatedString, array $variablesContext): string
    {
        return array_reduce(
            $interpolatedString->parts,
            fn ($carry, \PhpParser\Node $current): string => $carry.self::process($current, $variablesContext),
            initial: '',
        );
    }
}
