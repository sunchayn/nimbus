<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Config\Enums\EnvironmentIdEnum;

/**
 * A request ready to be sent. The path has its parameters filled in already.
 */
readonly class ExecutionRequestData
{
    /**
     * @param  array<string, string|list<string>>  $query
     * @param  array<string, string>  $headers
     * @param  array<array-key, mixed>|string  $body
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $headers = [],
        public array|string $body = [],
        public bool $transactionMode = false,
        public string $environment = EnvironmentIdEnum::Current->value,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'query' => $this->query,
            'headers' => $this->headers,
            'body' => $this->body,
            'transactionMode' => $this->transactionMode,
            'environment' => $this->environment,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            method: (string) ($data['method'] ?? 'GET'),
            path: (string) ($data['path'] ?? '/'),
            query: (array) ($data['query'] ?? []),
            headers: (array) ($data['headers'] ?? []),
            body: is_string($data['body'] ?? null) ? $data['body'] : (array) ($data['body'] ?? []),
            transactionMode: (bool) ($data['transactionMode'] ?? false),
            environment: (string) ($data['environment'] ?? EnvironmentIdEnum::Current->value),
        );
    }
}
