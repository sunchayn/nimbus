<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Services;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Routes\Services\Snapshot\ManifestManager;

/**
 * Runs a callback while a given application is the active one.
 *
 * Nimbus follows the active application through its resolver, and a snapshot manager is bound to that resolver.
 * Both are swapped in the container for the callback and put back as they were when it ends.
 * Anything the callback needs to follow the application must be resolved inside it.
 */
class ApplicationScope
{
    public function __construct(
        private readonly Container $container,
        private readonly Repository $repository,
        private readonly Request $request,
    ) {}

    /**
     * @template TResult
     *
     * @param  Closure(ActiveApplicationResolver): TResult  $callback  Receives the resolver of the application.
     * @param  ?string  $application  The application key. The default application is used when it is null.
     * @return TResult
     *
     * @throws McpToolException
     */
    public function run(?string $application, Closure $callback): mixed
    {
        $key = $this->resolveKey(application: $application);

        $previousResolver = $this->findSharedInstance(abstract: ActiveApplicationResolver::class);
        $previousManifestManager = $this->findSharedInstance(abstract: ManifestManager::class);

        $resolver = new ActiveApplicationResolver(
            $this->repository,
            $this->request->duplicate(cookies: [ActiveApplicationResolver::CURRENT_APPLICATION_COOKIE_NAME => $key]),
        );

        $this->container->instance(ActiveApplicationResolver::class, $resolver);
        $this->container->forgetInstance(ManifestManager::class);

        try {
            return $callback($resolver);
        } finally {
            $this->restore(abstract: ActiveApplicationResolver::class, instance: $previousResolver);
            $this->restore(abstract: ManifestManager::class, instance: $previousManifestManager);
        }
    }

    /**
     * @return list<string>
     */
    public function listKeys(): array
    {
        return array_map(
            strval(...),
            array_keys((array) $this->repository->get('nimbus.applications', [])),
        );
    }

    /**
     * @throws McpToolException
     */
    private function resolveKey(?string $application): string
    {
        $key = $application ?? (string) $this->repository->get('nimbus.default_application');

        $keys = $this->listKeys();

        if (! in_array($key, $keys, true)) {
            throw McpToolException::becauseApplicationIsUnknown(
                application: $key,
                known: $keys,
            );
        }

        return $key;
    }

    private function findSharedInstance(string $abstract): ?object
    {
        return $this->container->isShared($abstract) ? $this->container->make($abstract) : null;
    }

    private function restore(string $abstract, ?object $instance): void
    {
        if ($instance === null) {
            $this->container->forgetInstance($abstract);

            return;
        }

        $this->container->instance($abstract, $instance);
    }
}
