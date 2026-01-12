<?php

namespace Sunchayn\Nimbus\Modules\Config;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Sunchayn\Nimbus\Modules\Config\Exceptions\MisconfiguredValueException;
use Sunchayn\Nimbus\Modules\Relay\Authorization\Contracts\SpecialAuthenticationInjectorContract;

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

    public function getApiBaseUrl(): string
    {
        return $this->activeApplicationConfig['routes']['api_base_url'] ?? $this->request->getSchemeAndHttpHost();
    }

    public function getRoutesPrefix(): string
    {
        return $this->activeApplicationConfig['routes']['prefix'] ?? 'api';
    }

    public function getAuthGuard(): string
    {
        return $this->activeApplicationConfig['auth']['guard'] ?? 'web';
    }

    /** @return ?class-string<SpecialAuthenticationInjectorContract> */
    public function getSpecialAuthInjector(): ?string
    {
        return $this->activeApplicationConfig['auth']['special']['injector'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->activeApplicationConfig['headers'] ?? [];
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
