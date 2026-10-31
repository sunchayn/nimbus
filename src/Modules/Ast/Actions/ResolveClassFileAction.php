<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Ast\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use ReflectionClass;
use Sunchayn\Nimbus\Modules\Ast\Events\ClassFileResolved;

/**
 * Finds the source file of a class and announces it.
 */
class ResolveClassFileAction
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * @param  class-string  $className
     */
    public function execute(string $className): ?string
    {
        $fileName = (new ReflectionClass($className))->getFileName();

        if (! $fileName) {
            return null;
        }

        $this->dispatcher->dispatch(new ClassFileResolved($fileName));

        return $fileName;
    }
}
