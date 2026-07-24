<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Actions;

use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Resolve the PHP's type of a simple AST literal or scalar node.
 *
 * @example execute(Node\Scalar\Int_(42)) -> 'integer'
 * @example execute(Node\Expr\Variable('x')) -> null
 *
 * @final
 */
class GetPhpTypeFromAstScalarAction
{
    /**
     * Resolves primitive type name from simple AST literal and scalar nodes.
     */
    public function execute(Node|Expr $expr): ?string
    {
        return match (true) {
            $expr instanceof Node\Scalar\String_ => 'string',
            $expr instanceof Node\Scalar\Int_ => 'integer',
            $expr instanceof Node\Scalar\Float_ => 'number',
            // Constants (true, false, null) are encoded as ConstFetch nodes.
            $expr instanceof Node\Expr\ConstFetch => $this->resolveConstFetchType($expr),
            default => null,
        };
    }

    private function resolveConstFetchType(Node\Expr\ConstFetch $constFetch): ?string
    {
        $name = $constFetch->name->toLowerString();

        return match ($name) {
            'true', 'false' => 'boolean',
            default => null,
        };
    }
}
