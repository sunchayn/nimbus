<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Actions;

use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;

/**
 * Resolves the Eloquent model class associated with a JsonResource.
 *
 * @example execute(ClassQuery::from(UserResource::class)) // -> 'App\Models\User'
 *
 * @final
 */
class InferEloquentModelFromJsonResourceAction
{
    /**
     * @return class-string|null
     */
    public function execute(ClassQuery $classQuery): ?string
    {
        $docBlock = $classQuery->getClassDocBlock();

        return $this->resolveFromPropertyDocBlock($docBlock)
            ?? $this->resolveFromMixinDocBlock($docBlock)
            ?? $this->resolveFromConvention($classQuery->className());
    }

    /**
     * @return class-string|null
     */
    private function resolveFromPropertyDocBlock(string $docBlock): ?string
    {
        if (! preg_match('/@property(?:-read)?\s+([\\\a-zA-Z0-9_]+)\s+\$resource/', $docBlock, $matches)) {
            return null;
        }

        $propertyClass = ltrim($matches[1], '\\');

        if (! class_exists($propertyClass)) {
            return null;
        }

        /** @var class-string $propertyClass */
        return $propertyClass;
    }

    /**
     * @return class-string|null
     */
    private function resolveFromMixinDocBlock(string $docBlock): ?string
    {
        if (! preg_match('/@mixin\s+([\\\a-zA-Z0-9_]+)/', $docBlock, $matches)) {
            return null;
        }

        $mixinClass = ltrim($matches[1], '\\');

        if (! class_exists($mixinClass)) {
            return null;
        }

        /** @var class-string $mixinClass */
        return $mixinClass;
    }

    /**
     * @todo [ENHANCEMENT] Account for when the Model is not in the default app namespace.
     *
     * @return class-string|null
     */
    private function resolveFromConvention(string $fullClassName): ?string
    {
        $modelName = preg_replace('/Resource$/', '', class_basename($fullClassName));

        // E.g. UserResource -> App\Models\User.
        $guessedClass = 'App\Models\\'.$modelName;

        if (! class_exists($guessedClass)) {
            return null;
        }

        /** @var class-string $guessedClass */
        return $guessedClass;
    }
}
