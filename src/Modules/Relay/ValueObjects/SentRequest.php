<?php

namespace Sunchayn\Nimbus\Modules\Relay\ValueObjects;

use Psr\Http\Message\RequestInterface;
use Throwable;

readonly class SentRequest
{
    public const MAX_BODY_BYTES = 262_144;

    /**
     * @param  array<array{key: string, value: string}>  $headers
     */
    public function __construct(
        public string $method,
        public string $url,
        public string $protocolVersion,
        public array $headers,
        public string $body,
        public bool $bodyTruncated,
    ) {}

    /**
     * Describes the request as it left the relay, after authorization and encoding.
     */
    public static function fromPsrRequest(RequestInterface $request): self
    {
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = ['key' => $name, 'value' => $value];
            }
        }

        $stream = $request->getBody();

        try {
            $stream->rewind();
            $body = $stream->read(self::MAX_BODY_BYTES);
            $truncated = ! $stream->eof();
        } catch (Throwable) {
            $body = '';
            $truncated = false;
        }

        return new self(
            method: $request->getMethod(),
            url: (string) $request->getUri(),
            protocolVersion: $request->getProtocolVersion(),
            headers: $headers,
            body: $body,
            bodyTruncated: $truncated,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'url' => $this->url,
            'protocolVersion' => $this->protocolVersion,
            'headers' => $this->headers,
            'body' => $this->body,
            'bodyTruncated' => $this->bodyTruncated,
        ];
    }
}
