<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Extractor\Ast\Shared;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

/**
 * @codeCoverageIgnore covered in used classes.
 */
trait QualifiesTypehint
{
    /**
     * Normalizes how classes are referenced in type hinting so that we can have more conclusive equality checks later on.
     *
     * @param  Node[]  $nodes
     * @return Node[] $nodes
     */
    protected function qualifyClassTypeHinting(array $nodes): array
    {
        $nodeTraverser = new NodeTraverser;

        $nodeTraverser->addVisitor(new NameResolver);

        return $nodeTraverser->traverse($nodes);
    }
}
