<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Actions;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use ReflectionClass;

/**
 * Parses a class file into a fully qualified PhpParser AST.
 *
 * @example execute(UserResource::class) -> [PhpParser\Node\Stmt\Declare_, PhpParser\Node\Stmt\Namespace_, ...]
 *
 * @final
 */
class LoadClassAstAction
{
    /**
     * @param  class-string  $className
     * @return Node[]|null
     */
    public function execute(string $className): ?array
    {
        if (! class_exists($className)) {
            return null;
        }

        $fileName = (new ReflectionClass($className))->getFileName();

        if (! $fileName) {
            return null;
        }

        /**
         * Add this point the content cannot be null as the class is reflected successfully.
         *
         * @var string $code
         */
        $code = file_get_contents($fileName);

        return $this->parseCode($code);
    }

    /**
     * @return Node[]
     */
    private function parseCode(string $code): array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $stmts = $parser->parse($code) ?? [];

        // NameResolver traverses all nodes and rewrites Name nodes to their fully qualified
        // form `Request` to `\Illuminate\Http\Request`. This makes typehint matching robust regardless
        // of which `use` imports the source file declares.
        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new NameResolver);

        return $nodeTraverser->traverse($stmts);
    }
}
