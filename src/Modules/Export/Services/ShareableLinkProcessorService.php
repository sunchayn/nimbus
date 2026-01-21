<?php

namespace Sunchayn\Nimbus\Modules\Export\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Decodes and processes shareable link payloads.
 *
 * Decompresses the URL-safe base64 payload and discovers which
 * application the shared route belongs to.
 */
class ShareableLinkProcessorService
{
    /** @var array<string, mixed>|null */
    protected ?array $decodedPayload = null;

    protected bool $routeExists = true;

    protected ?string $targetApplication = null;

    protected ?string $error = null;

    public function __construct(
        protected Repository $config,
    ) {}

    public function process(string $shareParam): self
    {
        try {
            $this->decodedPayload = $this->decodePayload($shareParam);
            $this->discoverRoute();
        } catch (\Exception $exception) {
            $this->error = $exception->getMessage();
            $this->routeExists = false;
        }

        return $this;
    }

    public function hasPayload(): bool
    {
        return $this->decodedPayload !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDecodedPayload(): ?array
    {
        return $this->decodedPayload;
    }

    public function routeExists(): bool
    {
        return $this->routeExists;
    }

    public function getTargetApplication(): ?string
    {
        return $this->targetApplication;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return array{payload: array<string, mixed>|null, routeExists: bool, error: string|null}
     */
    public function toFrontendState(): array
    {
        return [
            'payload' => $this->decodedPayload,
            'routeExists' => $this->routeExists,
            'error' => $this->error,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    protected function decodePayload(string $encoded): array
    {
        $base64 = $this->restoreBase64FromUrlSafe($encoded);
        $compressed = $this->decodeBase64($base64);
        $json = $this->decompress($compressed);

        return $this->parseJson($json);
    }

    private function restoreBase64FromUrlSafe(string $encoded): string
    {
        $base64 = strtr($encoded, '-_', '+/');
        $padding = strlen($base64) % 4;

        return $padding > 0
            ? $base64.str_repeat('=', 4 - $padding)
            : $base64;
    }

    private function decodeBase64(string $base64): string
    {
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new \RuntimeException('Failed to decode base64 payload');
        }

        return $decoded;
    }

    private function decompress(string $compressed): string
    {
        $json = @gzuncompress($compressed);

        if ($json === false) {
            throw new \RuntimeException('Failed to decompress payload');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJson(string $json): array
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse JSON payload: '.json_last_error_msg());
        }

        return $decoded ?? [];
    }

    protected function discoverRoute(): void
    {
        $method = $this->decodedPayload['method'] ?? 'GET';
        $endpoint = $this->decodedPayload['endpoint'] ?? '/';

        $this->searchRouteInOtherApplications($method, $endpoint);

        if ($this->targetApplication !== null) {
            return;
        }

        if ($this->routeExistsInCurrentApplication($method, $endpoint)) {
            return;
        }

        $this->routeExists = false;
    }

    private function routeExistsInCurrentApplication(string $method, string $endpoint): bool
    {
        return $this->findMatchingRoute($method, $endpoint) !== null;
    }

    private function searchRouteInOtherApplications(string $method, string $endpoint): void
    {
        /** @var array<string, array{routes?: array{prefix?: string}}> $applications */
        $applications = $this->config->get('nimbus.applications', []);

        foreach ($applications as $applicationKey => $appConfig) {
            $prefix = $appConfig['routes']['prefix'] ?? 'api';

            if ($this->findMatchingRoute($method, $endpoint, $prefix) !== null) {
                $this->targetApplication = $applicationKey;

                return;
            }
        }
    }

    private function findMatchingRoute(string $method, string $endpoint, ?string $prefix = null): ?Route
    {
        /** @var RouteCollection $routes */
        $routes = RouteFacade::getRoutes();

        foreach ($routes as $route) {
            if ($this->routeMatches($route, $method, $endpoint, $prefix)) {
                return $route;
            }
        }

        return null;
    }

    private function routeMatches(Route $route, string $method, string $endpoint, ?string $prefix = null): bool
    {
        if (! $this->methodMatches($route, $method)) {
            return false;
        }

        $normalizedEndpoint = $this->normalizePath($endpoint);

        if ($prefix !== null && ! str_starts_with($normalizedEndpoint, $this->normalizePath($prefix))) {
            return false;
        }

        return $this->pathMatchesRoutePattern($normalizedEndpoint, $route->uri());
    }

    private function methodMatches(Route $route, string $method): bool
    {
        $routeMethods = array_map('strtoupper', $route->methods());

        return in_array(strtoupper($method), $routeMethods, true);
    }

    private function normalizePath(string $path): string
    {
        return '/'.ltrim($path, '/');
    }

    private function pathMatchesRoutePattern(string $path, string $routeUri): bool
    {
        $normalizedRouteUri = $this->normalizePath($routeUri);
        $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $normalizedRouteUri);

        return (bool) preg_match('#^'.$pattern.'$#', $path);
    }
}
