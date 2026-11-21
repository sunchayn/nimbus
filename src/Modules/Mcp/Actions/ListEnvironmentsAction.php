<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Actions;

use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Config\DataTransferObjects\EnvironmentData;

/** @final */
class ListEnvironmentsAction
{
    public function __construct(private readonly ActiveApplicationResolver $activeApplicationResolver) {}

    /**
     * @return array{environments: list<array<string, mixed>>}
     */
    public function execute(): array
    {
        return [
            'environments' => array_map(
                static fn (EnvironmentData $environment): array => $environment->toArray(),
                $this->activeApplicationResolver->getEnvironments(),
            ),
        ];
    }
}
