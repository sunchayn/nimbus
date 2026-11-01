<?php

namespace Sunchayn\Nimbus\Modules\Config;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Config\DataTransferObjects\EnvironmentData;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentIdEnum;
use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentSourceEnum;
use Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Contracts\SpecialAuthenticationInjectorContract;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Injectors\RememberMeCookieInjector;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Injectors\TymonJwtTokenInjector;

class ActiveApplicationResolver
{
    public const CURRENT_APPLICATION_COOKIE_NAME = 'nimbus:application';

    /** @var array<string, mixed> */
    protected readonly array $activeApplicationConfig;

    protected readonly string $activeApplicationKey;

    /**
     * @throws MisconfiguredValueException
     */
    public function __construct(
        protected Repository $config,
        protected Request $request
    ) {
        $this->activeApplicationKey = $this->determineActiveApplicationKey();

        $this->activeApplicationConfig = $this->getActiveApplicationConfig();
    }

    public function getActiveApplicationKey(): string
    {
        return $this->activeApplicationKey;
    }

    public function isVersioned(): bool
    {
        return $this->activeApplicationConfig['routes']['versioned'] ?? false;
    }

    public function showOperationId(): bool
    {
        return $this->activeApplicationConfig['routes']['openapi']['show_operation_id'] ?? false;
    }

    public function getApiBaseUrl(): string
    {
        return $this->config->get('nimbus.base_url') ?? $this->request->getSchemeAndHttpHost();
    }

    /**
     * The current host comes first, then the environments declared in the config.
     * A config environment that matches the current host is merged into it.
     *
     * @return list<EnvironmentData>
     *
     * @throws MisconfiguredValueException
     */
    public function getEnvironments(): array
    {
        $configured = [];

        foreach ($this->config->get('nimbus.environments', []) as $key => $environment) {
            $key = (string) $key;

            if ($key === EnvironmentIdEnum::Current->value) {
                continue;
            }

            $baseUrl = $environment['base_url'] ?? null;

            if (! is_string($baseUrl) || ! in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true) || ! parse_url($baseUrl, PHP_URL_HOST)) {
                throw MisconfiguredValueException::becauseEnvironmentIsInvalid($key);
            }

            $configured[$key] = new EnvironmentData(
                id: $key,
                name: (string) ($environment['name'] ?? $key),
                baseUrl: rtrim($baseUrl, '/'),
                enabled: (bool) ($environment['enabled'] ?? false),
                source: EnvironmentSourceEnum::Config,
            );
        }

        $detected = $this->detectCurrentHostEnvironment($configured);

        $environmentData = new EnvironmentData(
            id: EnvironmentIdEnum::Current->value,
            name: $detected === null ? $this->undetectedHostName() : $configured[$detected]->name.' (current host)',
            baseUrl: $this->getApiBaseUrl(),
            enabled: true,
            source: EnvironmentSourceEnum::Current,
            detected: $detected,
        );

        if ($detected !== null) {
            unset($configured[$detected]);
        }

        return [$environmentData, ...array_values($configured)];
    }

    /**
     * Names the host Local when it runs locally, since it matches no configured environment.
     */
    private function undetectedHostName(): string
    {
        $host = strtolower((string) parse_url((string) $this->config->get('app.url'), PHP_URL_HOST));
        $isLoopback = in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true);

        return $isLoopback || strtolower((string) $this->config->get('app.env')) === 'local'
            ? 'Local'
            : 'Current host';
    }

    /**
     * Matches the app URL host and port first, then falls back to the app environment name.
     *
     * @param  array<string, EnvironmentData>  $configured
     */
    private function detectCurrentHostEnvironment(array $configured): ?string
    {
        $hostAndPort = fn (string $url): string => strtolower(parse_url($url, PHP_URL_HOST).':'.(parse_url($url, PHP_URL_PORT) ?? ''));

        $appUrl = $this->config->get('app.url');

        if (is_string($appUrl) && $appUrl !== '') {
            foreach ($configured as $key => $environment) {
                if ($hostAndPort($environment->baseUrl) === $hostAndPort($appUrl)) {
                    return (string) $key;
                }
            }
        }

        $appEnv = strtolower((string) $this->config->get('app.env'));

        foreach (array_keys($configured) as $key) {
            if ($appEnv !== '' && strtolower((string) $key) === $appEnv) {
                return (string) $key;
            }
        }

        return null;
    }

    public function getRoutesPrefix(): string
    {
        $prefix = $this->activeApplicationConfig['routes']['prefix'] ?? 'api';

        return trim($prefix, '/');
    }

    public function getAuthGuard(): string
    {
        return $this->activeApplicationConfig['auth']['guard'] ?? 'web';
    }

    /** @return ?class-string<SpecialAuthenticationInjectorContract> */
    public function getSpecialAuthInjector(): ?string
    {
        $rawInjector = $this->activeApplicationConfig['auth']['special']['injector'] ?? null;

        if ($rawInjector === null || $rawInjector === '') {
            return null;
        }

        if (is_string($rawInjector)) {
            $normalized = strtolower(trim($rawInjector));

            /** @var class-string<SpecialAuthenticationInjectorContract> $mappedClass */
            $mappedClass = match ($normalized) {
                'remember_me_cookie' => RememberMeCookieInjector::class,
                'tymon_jwt' => TymonJwtTokenInjector::class,
                default => $rawInjector,
            };

            return $mappedClass;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->activeApplicationConfig['headers'] ?? [];
    }

    /**
     * Get the route extraction strategy for the active application.
     *
     * @throws MisconfiguredValueException
     */
    public function getRouteExtractionStrategy(): RoutesProcessingStrategyEnum
    {
        $rawStrategy = $this->activeApplicationConfig['routes']['strategy'] ?? RoutesProcessingStrategyEnum::AutoDetect;

        $strategy = RoutesProcessingStrategyEnum::tryFromAlias($rawStrategy);

        if (! $strategy instanceof \Sunchayn\Nimbus\Modules\Config\Enums\RoutesProcessingStrategyEnum) {
            throw MisconfiguredValueException::becauseRouteProcessingStrategyIsInvalid($rawStrategy);
        }

        return $strategy;
    }

    /**
     * Get the OpenAPI file mappings (version => file path) for the active application.
     *
     * @return array<string, string>
     */
    public function getOpenApiFiles(): array
    {
        return $this->activeApplicationConfig['routes']['openapi']['files'] ?? [];
    }

    public function getAvailableApplications(): string
    {
        $applications = $this->config->get('nimbus.applications', []);

        return (string) json_encode(
            Arr::mapWithKeys(
                $applications,
                fn (array $config, string $key): array => [
                    $key => $config['name'] ?? $key,
                ],
            ),
        );
    }

    /**
     * @throws MisconfiguredValueException
     */
    protected function determineActiveApplicationKey(): string
    {
        $applications = $this->config->get('nimbus.applications', []);

        if ($applications === []) {
            throw MisconfiguredValueException::becauseApplicationsAreNotDefined();
        }

        $applicationKey = $this->request->cookie(self::CURRENT_APPLICATION_COOKIE_NAME);

        if (is_string($applicationKey) && array_key_exists($applicationKey, $applications)) {
            return $applicationKey;
        }

        $applicationKey = $this->config->get('nimbus.default_application');

        if (! array_key_exists($applicationKey, $applications)) {
            throw MisconfiguredValueException::becauseDefaultApplicationIsInvalid($applicationKey);
        }

        return (string) $applicationKey;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getActiveApplicationConfig(): array
    {
        return $this->config->get('nimbus.applications.'.$this->activeApplicationKey, []);
    }
}
