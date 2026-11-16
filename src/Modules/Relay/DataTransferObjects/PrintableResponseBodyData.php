<?php

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Illuminate\Http\Client\Response;

readonly class PrintableResponseBodyData
{
    /**
     * @param  string|array<array-key, mixed>  $body
     */
    public function __construct(
        public array|string $body,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self(
            body: $response->json() ?? $response->body(),
        );
    }

    public function toPrettyJSON(): string
    {
        if (is_array($this->body)) {
            return json_encode($this->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return $this->body;
    }
}
