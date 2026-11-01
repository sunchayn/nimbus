<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Config\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentSourceEnum;

readonly class EnvironmentData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $baseUrl,
        public bool $enabled,
        public EnvironmentSourceEnum $source,
        public ?string $detected = null,
    ) {}

    /**
     * @return array{id: string, name: string, baseUrl: string, enabled: bool, source: string, detected: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'baseUrl' => $this->baseUrl,
            'enabled' => $this->enabled,
            'source' => $this->source->value,
            'detected' => $this->detected,
        ];
    }
}
