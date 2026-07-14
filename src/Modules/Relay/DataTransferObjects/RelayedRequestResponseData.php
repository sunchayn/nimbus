<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Relay\ValueObjects\PrintableResponseBody;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;

readonly class RelayedRequestResponseData
{
    /**
     * @param  string[][]  $headers
     * @param  ResponseCookieValueObject[]  $cookies
     */
    public function __construct(
        public int $statusCode,
        public string $statusText,
        public PrintableResponseBody $body,
        public array $headers,
        public float $durationMs,
        public int $timestamp,
        public array $cookies,
    ) {}
}
