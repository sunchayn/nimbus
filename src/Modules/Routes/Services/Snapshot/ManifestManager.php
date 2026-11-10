<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Routes\Services\Snapshot;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use LogicException;
use Sunchayn\Nimbus\Modules\Ast\Events\ClassFileResolved;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Routes\Collections\ExtractedRoutesCollection;

/**
 * Keeps the manifest of a routes extraction, which is a snapshot of everything it depends on.
 *
 * @phpstan-type Manifest array{
 *     config: string,
 *     routes: string,
 *     files: array<string, array{int, int}>,
 *     created_at: int,
 *     extracted: ExtractedRoutesCollection,
 * }
 */
class ManifestManager
{
    // Edits within the same clock second can keep the same size and time, so recent files are never trusted.
    private const TRUSTED_TIME_MARGIN_IN_SECONDS = 2;

    private const DIRECTORY = 'vendor/sunchayn/nimbus/storage';

    /** @var array<string, true> */
    private array $files = [];

    private ?ExtractedRoutesCollection $extractedRoutes = null;

    /** @var Manifest|null */
    private ?array $manifest = null;

    private bool $isLoaded = false;

    public function __construct(
        private readonly Router $router,
        private readonly Repository $repository,
        private readonly ActiveApplicationResolver $activeApplicationResolver,
    ) {}

    public function reset(): void
    {
        $this->files = [];
        $this->extractedRoutes = null;
    }

    public function trackFile(ClassFileResolved $classFileResolved): void
    {
        $path = $classFileResolved->path;

        // Vendor classes change only through composer, which the lock file already covers.
        if (str_starts_with($path, base_path('vendor').DIRECTORY_SEPARATOR)) {
            return;
        }

        $this->files[$path] = true;
    }

    /**
     * Cache the list of extracted routes to serve on cache-hit for the snapshot.
     */
    public function setExtractedRoutes(ExtractedRoutesCollection $extractedRoutes): void
    {
        $this->extractedRoutes = $extractedRoutes;
    }

    /**
     * Builds the manifest of the files and routes tracked since the last reset and saves it to disk.
     */
    public function save(): void
    {
        $this->manifest = $this->prepareForStorage(
            $this->extractedRoutes ?? throw new LogicException('No routes are tracked.'),
        );

        $this->isLoaded = true;

        $path = $this->manifestPath();

        $directory = dirname($path);

        // An unwritable directory must not fail the request,
        // and a parallel request may create it first.
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return;
        }

        // The rename keeps readers from ever seeing a half written file.
        $temporaryPath = $directory.'/.'.uniqid('snapshot_', true).'.tmp';

        $isWritten = @file_put_contents($temporaryPath, serialize($this->manifest)) !== false
            && @rename($temporaryPath, $path);

        if (! $isWritten) {
            @unlink($temporaryPath);
        }
    }

    /**
     * Load the saved manifest from the filesystem.
     *
     * @return Manifest|null
     */
    public function load(): ?array
    {
        if ($this->isLoaded) {
            return $this->manifest;
        }

        $path = $this->manifestPath();

        $manifest = null;

        try {
            $manifest = @unserialize(File::get($path));
        } catch (FileNotFoundException) {
        }

        $this->isLoaded = true;

        return $this->manifest = $this->isValidManifest($manifest)
            ? $manifest
            : null;
    }

    /**
     * A missing manifest is stale.
     * The cheapest checks run first and the first change stops the verification.
     */
    public function isStale(): bool
    {
        $manifest = $this->load();

        if ($manifest === null) {
            return true;
        }

        if ($manifest['config'] !== $this->configFingerprint()) {
            return true;
        }

        if ($manifest['routes'] !== $this->routesFingerprint()) {
            return true;
        }

        // PHP caches stat results,
        // so clear them to see edits made earlier in this process.
        clearstatcache();

        foreach ($manifest['files'] as $path => [$size, $mtime]) {
            $stat = @stat($path);

            if (! $stat || $stat['size'] !== $size || $stat['mtime'] !== $mtime) {
                return true;
            }

            // Without a content hash, a file modified close to the snapshot cannot be trusted.
            if ($mtime >= $manifest['created_at'] - self::TRUSTED_TIME_MARGIN_IN_SECONDS) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws LogicException
     */
    public function getExtractedRoutes(): ExtractedRoutesCollection
    {
        return $this->load()['extracted'] ?? throw new LogicException('No manifest is loaded.');
    }

    public function createdAt(): ?int
    {
        return $this->load()['created_at'] ?? null;
    }

    public function clear(): void
    {
        $this->manifest = null;
        $this->isLoaded = true;

        @unlink($this->manifestPath());
    }

    public function directory(): string
    {
        return base_path(self::DIRECTORY);
    }

    public function clearAll(): void
    {
        $this->manifest = null;
        $this->isLoaded = true;

        foreach (glob(base_path(self::DIRECTORY).'/routes_snapshot_*.snapshot') ?: [] as $path) {
            @unlink($path);
        }
    }

    /**
     * @return Manifest
     */
    private function prepareForStorage(ExtractedRoutesCollection $extractedRoutes): array
    {
        $paths = [
            ...array_keys($this->files),
            base_path('composer.lock'),
        ];

        if ($this->activeApplicationResolver->getRouteExtractionStrategy() === RoutesProcessingStrategyEnum::OpenAPI) {
            $paths = [...$paths, ...array_values($this->activeApplicationResolver->getOpenApiFiles())];
        }

        // PHP caches stat results, so clear them to see edits made earlier in this process.
        clearstatcache();

        $files = [];

        foreach (array_unique($paths) as $path) {
            $stat = @stat($path);

            // A file deleted since it was tracked is left out,
            // so it cannot block later checks.
            if ($stat) {
                $files[$path] = [
                    $stat['size'],
                    $stat['mtime'],
                ];
            }
        }

        return [
            'config' => $this->configFingerprint(),
            'routes' => $this->routesFingerprint(),
            'files' => $files,
            'created_at' => time(),
            'extracted' => $extractedRoutes,
        ];
    }

    private function configFingerprint(): string
    {
        return hash('xxh3', (string) json_encode(
            $this->repository->get(
                'nimbus.applications.'.$this->activeApplicationResolver->getActiveApplicationKey()
            ),
        ));
    }

    private function routesFingerprint(): string
    {
        $prefix = $this->activeApplicationResolver->getRoutesPrefix();

        $routes = collect($this->router->getRoutes()->getRoutes())
            // Routes outside the active prefix are never extracted, so they cannot change the manifest.
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), $prefix) || str_starts_with($route->uri(), '/'.$prefix))
            // Sorting keeps the fingerprint the same when routes are registered in a different order.
            ->sortBy(fn (Route $route): string => $route->uri().'|'.implode(',', $route->methods()))
            ->map(function (Route $route): array {
                $uses = $route->getAction('uses');

                return [
                    $route->uri(),
                    $route->methods(),
                    $route->getName(),
                    // Closure routes have no stable identity, so they only count as closures.
                    is_string($uses) ? $uses : 'closure',
                    // Closure middleware cannot be encoded, so only named middleware is compared.
                    array_values(array_filter($route->gatherMiddleware(), is_string(...))),
                ];
            })
            ->values();

        return hash('xxh3', (string) json_encode($routes));
    }

    private function manifestPath(): string
    {
        // Application keys come from config, so make any key safe to use as a file name.
        $name = preg_replace('/[^A-Za-z0-9_-]/', '_', $this->activeApplicationResolver->getActiveApplicationKey());

        return base_path(self::DIRECTORY.'/routes_snapshot_'.$name.'.snapshot');
    }

    /**
     * @phpstan-assert-if-true Manifest $manifest
     */
    private function isValidManifest(mixed $manifest): bool
    {
        return is_array($manifest)
            && is_string($manifest['config'] ?? null)
            && is_string($manifest['routes'] ?? null)
            && is_array($manifest['files'] ?? null)
            && is_int($manifest['created_at'] ?? null)
            && ($manifest['extracted'] ?? null) instanceof ExtractedRoutesCollection;
    }
}
