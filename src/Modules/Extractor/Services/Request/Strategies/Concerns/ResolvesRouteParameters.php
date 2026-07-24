<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Request\Strategies\Concerns;

use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Provides shared reflection utilities for request schema strategies.
 */
trait ResolvesRouteParameters
{
    /**
     * @return ReflectionParameter[]
     */
    private function getRouteParameters(Route $route): array
    {
        $class = $route->getControllerClass();
        $method = $route->getActionMethod();

        if ($class === null || ! class_exists($class) || ! method_exists($class, $method)) {
            return [];
        }

        return (new ReflectionMethod($class, $method))->getParameters();
    }

    /**
     * Finds the first reflection parameter whose named type matches or extends $targetClass.
     *
     * @param  ReflectionParameter[]  $parameters
     * @param  class-string  $parentClass
     */
    private function findChildOf(
        array $parameters,
        string $parentClass,
    ): ?ReflectionParameter {
        foreach ($parameters as $parameter) {
            if (! $parameter->hasType()) {
                continue;
            }

            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            if (class_exists($typeName) && is_subclass_of($typeName, $parentClass)) {
                return $parameter;
            }
        }

        return null;
    }
}
