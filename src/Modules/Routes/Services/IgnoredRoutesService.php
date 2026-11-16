<?php

namespace Sunchayn\Nimbus\Modules\Routes\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;

/**
 * Manages routes that should be excluded from schema extraction.
 *
 * Persists ignored routes to vendor directory to survive package updates,
 * allowing developers to maintain a blacklist of problematic routes.
 */
class IgnoredRoutesService
{
    private const STORAGE_PATH = 'vendor/sunchayn/nimbus/storage/ignored_routes.json';

    /** @var array<string, non-empty-array{methods: string[], reason: string, ignored_at: string}> */
    private array $ignoredRoutes = [];

    private bool $isDirty = false;

    public function __construct()
    {
        $this->loadFromFile();
    }

    public function __destruct()
    {
        if (! $this->isDirty) {
            return;
        }

        $this->writeToFile();
    }

    /**
     * Adds a route to the ignored list with a reason for exclusion.
     *
     * Routes are ignored when extraction fails or when developers
     * explicitly want to exclude them from schema generation.
     *
     * @param  non-empty-string  $uri
     * @param  array<int, string>  $methods
     * @param  non-empty-string  $reason
     */
    public function add(string $uri, array $methods, string $reason = 'Route extraction failed'): void
    {
        $routeData = [
            'methods' => $methods,
            'reason' => $reason,
            'ignored_at' => CarbonImmutable::now()->toISOString() ?? date('Y-m-d H:i:s'),
        ];

        $this->ignoredRoutes[$uri] = $routeData;

        $this->isDirty = true;
    }

    /**
     * Removes specific HTTP methods from an ignored route.
     *
     * If all methods are removed, the entire route is removed from
     * the ignored list, allowing it to be processed again.
     *
     * @param  non-empty-string  $uri
     * @param  array<int, string>  $methods
     */
    public function remove(string $uri, array $methods): void
    {
        if (! isset($this->ignoredRoutes[$uri])) {
            return;
        }

        $this->ignoredRoutes[$uri]['methods'] = array_diff($this->ignoredRoutes[$uri]['methods'], $methods);

        if (empty($this->ignoredRoutes[$uri]['methods'])) {
            unset($this->ignoredRoutes[$uri]);
        }

        $this->isDirty = true;
    }

    /**
     * Checks if a route should be ignored during schema extraction.
     *
     * A route is ignored if any of its HTTP methods are in the ignored list,
     * allowing partial ignoring of routes with multiple methods.
     */
    public function isIgnored(Route $route): bool
    {
        $ignoredRoute = $this->ignoredRoutes[$route->uri()] ?? null;

        if ($ignoredRoute === null) {
            return false;
        }

        foreach ($route->methods() as $method) {
            if (in_array($method, $ignoredRoute['methods'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if there are any ignored routes without loading full data.
     *
     * Performs a lightweight check to avoid unnecessary file operations
     * when no routes are ignored.
     */
    public function hasIgnoredRoutes(): bool
    {
        if ($this->ignoredRoutes !== []) {
            return true;
        }

        try {
            $content = File::get(base_path(self::STORAGE_PATH));
        } catch (FileNotFoundException) {
            return false;
        }

        if ($content === '[]') {
            return false;
        }

        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Loads ignored routes from persistent storage.
     *
     * Gracefully handles missing or corrupted files by defaulting
     * to an empty ignored routes list.
     */
    private function loadFromFile(): void
    {
        $filePath = base_path(self::STORAGE_PATH);

        if (! File::exists($filePath)) {
            $this->ignoredRoutes = [];

            return;
        }

        $content = File::get($filePath);

        $this->ignoredRoutes = json_decode($content, true) ?: [];
    }

    /**
     * Persists ignored routes to storage with automatic directory creation.
     *
     * Creates the storage directory if it doesn't exist and ensures
     * the file is always valid JSON, even if encoding fails.
     */
    private function writeToFile(): void
    {
        $filePath = base_path(self::STORAGE_PATH);
        $directory = dirname($filePath);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        File::put(
            $filePath,
            json_encode($this->ignoredRoutes, JSON_PRETTY_PRINT) ?: '[]'
        );

        $this->isDirty = false;
    }
}
