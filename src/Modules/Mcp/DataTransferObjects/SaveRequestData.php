<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

readonly class SaveRequestData
{
    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<array-key, mixed>|string  $body
     */
    public function __construct(
        public string $name,
        public string $method,
        public string $endpoint,
        public ?string $route = null,
        public array $headers = [],
        public array $query = [],
        public array|string $body = [],
        public ?string $folder = null,
    ) {}
}
