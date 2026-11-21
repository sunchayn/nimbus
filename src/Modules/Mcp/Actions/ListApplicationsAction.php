<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Illuminate\Contracts\Config\Repository;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ApplicationGlobalHeaders;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\ApplicationScope;

/**
 * Lists the applications that routes can be listed from and requests can be sent to.
 *
 * @final
 */
class ListApplicationsAction
{
    public function __construct(
        private readonly ApplicationScope $applicationScope,
        private readonly Repository $repository,
    ) {}

    /**
     * @return array{applications: list<array<string, mixed>>}
     *
     * @throws McpToolException
     */
    public function execute(): array
    {
        $defaultApplication = (string) $this->repository->get('nimbus.default_application');

        return [
            'applications' => array_map(
                fn (string $key): array => $this->describe(key: $key, isDefault: $key === $defaultApplication),
                $this->applicationScope->listKeys(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws McpToolException
     */
    private function describe(string $key, bool $isDefault): array
    {
        return $this->applicationScope->run(
            $key,
            fn (ActiveApplicationResolver $resolver): array => [
                'key' => $key,
                'name' => (string) $this->repository->get("nimbus.applications.{$key}.name", $key),
                'routesPrefix' => $resolver->getRoutesPrefix(),
                'versioned' => $resolver->isVersioned(),
                'extractionStrategy' => $resolver->getRouteExtractionStrategy()->value,
                'authGuard' => $resolver->getAuthGuard(),
                'specialAuthInjector' => $resolver->getSpecialAuthInjector(),
                'globalHeaders' => ApplicationGlobalHeaders::fromResolver($resolver)->toArray(),
                'default' => $isDefault,
            ],
        );
    }
}
