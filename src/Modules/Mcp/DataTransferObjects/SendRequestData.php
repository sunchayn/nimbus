<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentIdEnum;

readonly class SendRequestData
{
    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $headers
     * @param  array<array-key, mixed>|string  $body
     */
    public function __construct(
        public ?string $route = null,
        public ?string $method = null,
        public ?string $uri = null,
        public array $params = [],
        public array $query = [],
        public array $headers = [],
        public array|string $body = [],
        public ?string $actor = null,
        public ?string $application = null,
        public string $environment = EnvironmentIdEnum::Current->value,
    ) {}
}
