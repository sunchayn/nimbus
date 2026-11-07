<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Collects the facts that explain how to call a route, without running any host code.
 *
 * Middleware is resolved through the router, so aliases and groups become their class names.
 * Only known authentication names give an auth requirement, anything else is "none".
 *
 * @final
 */
class ExtractRouteMetadataAction
{
    private const AUTH_NAMES = [
        'auth',
        'auth.basic',
        'auth.session',
        \Illuminate\Auth\Middleware\Authenticate::class,
        \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    ];

    private const AUTH_HINT_NAMES = [
        'can',
        'ability',
        'abilities',
        \Illuminate\Auth\Middleware\Authorize::class,
        'Laravel\Sanctum\Http\Middleware\CheckAbilities',
        'Laravel\Sanctum\Http\Middleware\CheckForAnyAbility',
    ];

    private const THROTTLE_NAMES = [
        'throttle',
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
    ];

    public function __construct(
        private readonly Router $router,
        private readonly Repository $repository,
    ) {}

    /**
     * Keys without a value are left out, only `auth` is always present.
     *
     * @return array<string, mixed>
     */
    public function execute(Route $route): array
    {
        $middleware = $this->resolveMiddleware($route);

        $authHints = $this->middlewareMatching($middleware, self::AUTH_HINT_NAMES);

        $metadata = [
            'middleware' => $middleware,
            'auth' => match (true) {
                $this->middlewareMatching($middleware, self::AUTH_NAMES) !== [] => 'required',
                $authHints !== [] => 'hint',
                default => 'none',
            },
            'authHints' => $authHints,
            'unknownGuards' => $this->findUnknownGuards($middleware),
            'throttle' => $this->findThrottle($middleware),
            'bindings' => $this->findBindings($route),
            'where' => $this->findWhere($route),
        ];

        return array_filter(
            $metadata,
            fn (mixed $value): bool => $value !== null && $value !== [],
        );
    }

    /**
     * @return string[]
     */
    private function resolveMiddleware(Route $route): array
    {
        $declared = array_filter($route->middleware(), is_string(...));

        return array_values(array_unique(
            $this->router->resolveMiddleware($declared, $route->excludedMiddleware())
        ));
    }

    /**
     * @param  string[]  $middleware
     * @param  string[]  $names
     * @return string[]
     */
    private function middlewareMatching(array $middleware, array $names): array
    {
        return array_values(array_filter(
            $middleware,
            fn (string $entry): bool => in_array(explode(':', $entry)[0], $names, true),
        ));
    }

    /**
     * Guards named by the authentication middleware that the app does not configure.
     * The guard is never resolved, because resolving an undefined guard throws.
     *
     * @param  string[]  $middleware
     * @return string[]
     */
    private function findUnknownGuards(array $middleware): array
    {
        $configured = array_keys((array) $this->repository->get('auth.guards', []));

        $named = [];

        foreach ($this->middlewareMatching($middleware, self::AUTH_NAMES) as $entry) {
            $parameters = str_contains($entry, ':') ? explode(':', $entry, 2)[1] : '';

            array_push($named, ...array_filter(explode(',', $parameters), fn (string $guard): bool => $guard !== ''));
        }

        return array_values(array_unique(array_diff($named, $configured)));
    }

    /**
     * Returns the limit as written, for example "60,1" or the name of a rate limiter.
     *
     * @param  string[]  $middleware
     */
    private function findThrottle(array $middleware): ?string
    {
        $entry = $this->middlewareMatching($middleware, self::THROTTLE_NAMES)[0] ?? null;

        if ($entry === null) {
            return null;
        }

        return str_contains($entry, ':') ? explode(':', $entry, 2)[1] : null;
    }

    /**
     * @return array<string, string>
     */
    private function findWhere(Route $route): array
    {
        $parameters = $route->parameterNames();

        return array_filter(
            $route->wheres,
            fn (string $pattern, string $parameter): bool => in_array($parameter, $parameters, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return list<array{parameter: string, model: string, key: ?string}>
     */
    private function findBindings(Route $route): array
    {
        $implicit = $this->implicitBindingTypes($route);

        $bindings = [];

        foreach ($route->parameterNames() as $parameter) {
            $model = $this->explicitBindingModel($parameter) ?? $implicit[$parameter] ?? null;

            if ($model === null) {
                continue;
            }

            $bindings[] = [
                'parameter' => $parameter,
                'model' => $model,
                'key' => $route->bindingFieldFor($parameter),
            ];
        }

        return $bindings;
    }

    /**
     * Reads the class stored by `Route::model()` without calling the binding.
     */
    private function explicitBindingModel(string $parameter): ?string
    {
        $callback = $this->router->getBindingCallback($parameter);

        if (! $callback instanceof Closure) {
            return null;
        }

        $class = (new ReflectionFunction($callback))->getClosureUsedVariables()['class'] ?? null;

        return is_string($class) ? $class : null;
    }

    /**
     * Maps each route parameter to the model type hint of the controller method, as Laravel does.
     *
     * @return array<string, string>
     */
    private function implicitBindingTypes(Route $route): array
    {
        $class = $route->getControllerClass();

        if ($class === null || ! class_exists($class) || ! method_exists($class, $route->getActionMethod())) {
            return [];
        }

        $types = [];

        foreach ((new ReflectionMethod($class, $route->getActionMethod()))->getParameters() as $reflectionParameter) {
            $type = $reflectionParameter->getType();

            if (! $type instanceof ReflectionNamedType || ! is_subclass_of($type->getName(), Model::class)) {
                continue;
            }

            foreach ([$reflectionParameter->getName(), Str::snake($reflectionParameter->getName())] as $name) {
                if (in_array($name, $route->parameterNames(), true)) {
                    $types[$name] = $type->getName();
                }
            }
        }

        return $types;
    }
}
