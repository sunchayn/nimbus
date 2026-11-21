<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;

/**
 * The raw answer of the application.
 */
class ExecutionResultData
{
    /**
     * @param  array<string, string>  $headers  Names are lowercase.
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
        public readonly float $durationMs,
        public readonly ?InsightData $insight = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'headers' => $this->headers,
            'body' => $this->body,
            'durationMs' => $this->durationMs,
            'insight' => $this->insight?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: (int) ($data['status'] ?? 0),
            headers: array_map(strval(...), (array) ($data['headers'] ?? [])),
            body: (string) ($data['body'] ?? ''),
            durationMs: (float) ($data['durationMs'] ?? 0),
            insight: is_array($data['insight'] ?? null) ? InsightData::fromArray($data['insight']) : null,
        );
    }
}
