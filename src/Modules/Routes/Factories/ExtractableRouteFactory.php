<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Factories;

use Illuminate\Routing\Route;
use ReflectionClass;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\InvalidRouteDefinitionException;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteExtractionException;

/**
 * Validates and prepares Laravel Route instances for schema extraction.
 */
class ExtractableRouteFactory
{
    /**
     * Prepares a Laravel Route for schema extraction.
     *
     * Returns null for routes that should be silently skipped (closures,
     * non-standard action formats). Throws RouteExtractionException for routes
     * that have malformed definitions.
     *
     * @throws RouteExtractionException
     */
    public function fromLaravelRoute(Route $route): ?Route
    {
        $uses = $route->getAction('uses');

        // Closure-based routes and non-string action values cannot be statically analysed.
        if (! is_string($uses) || $this->isSerializedRoute($uses)) {
            return null;
        }

        $parts = explode('@', $uses);
        $controllerClassName = $parts[0];
        $controllerMethod = $parts[1] ?? '';

        if ($controllerClassName === '' || $controllerClassName === '0' || ($controllerMethod === '' || $controllerMethod === '0')) {
            throw InvalidRouteDefinitionException::forRoute(
                routeUri: $route->uri(),
                routeMethods: $route->methods(),
                controllerClass: $controllerClassName,
                controllerMethod: $controllerMethod,
            );
        }

        if (! class_exists($controllerClassName) || ! method_exists($controllerClassName, $controllerMethod)) {
            throw InvalidRouteDefinitionException::forRoute(
                routeUri: $route->uri(),
                routeMethods: $route->methods(),
                controllerClass: $controllerClassName,
                controllerMethod: $controllerMethod,
            );
        }

        $fileName = (new ReflectionClass($controllerClassName))->getFileName();

        if ($fileName === false) {
            throw InvalidRouteDefinitionException::forRoute(
                routeUri: $route->uri(),
                routeMethods: $route->methods(),
                controllerClass: $controllerClassName,
                controllerMethod: $controllerMethod,
            );
        }

        return $route;
    }

    private function isSerializedRoute(string $value): bool
    {
        return str_starts_with($value, 'a:') || str_starts_with($value, 'O:');
    }
}
