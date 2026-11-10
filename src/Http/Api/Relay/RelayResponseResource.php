<?php

namespace Sunchayn\Nimbus\Http\Api\Relay;

use Illuminate\Http\Resources\Json\JsonResource;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\RelayedRequestResponseData;

/**
 * @property RelayedRequestResponseData $resource
 *
 * @mixin RelayedRequestResponseData
 */
class RelayResponseResource extends JsonResource
{
    public static $wrap;

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'statusCode' => $this->resource->statusCode,
            'statusText' => $this->resource->statusText,
            'body' => $this->resource->body->toPrettyJSON(),
            'headers' => $this->processHeaders($this->headers),
            'cookies' => collect($this->resource->cookies)->map->toArray(),
            'duration' => $this->resource->durationMs,
            'timestamp' => $this->resource->timestamp,
            'download' => $this->resource->download?->toArray(),
            'sent' => $this->resource->sent?->toArray(),
        ];
    }

    /**
     * Converts headers from backend format to frontend array format.
     *
     * @param  string[][]  $headers
     * @return array<array{key: string, value: string}>
     */
    private function processHeaders(array $headers): array
    {
        return collect($headers)
            ->flatMap(
                // Convert each header value to a separate key-value pair
                fn (array $values, string $key) => collect($values)
                    ->map(fn (string $value): array => [
                        'key' => $key,
                        'value' => $value,
                    ]),
            )
            ->values()
            ->all();
    }
}
