<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\DownloadResponseBody;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\PrintableResponseBody;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\ResponseCookieValueObject;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\SentRequest;

class RelayedRequestResponseData
{
    /**
     * @param  string[][]  $headers
     * @param  ResponseCookieValueObject[]  $cookies
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $statusText,
        public readonly PrintableResponseBody $body,
        public readonly array $headers,
        public readonly float $durationMs,
        public readonly int $timestamp,
        public readonly array $cookies,
        public readonly ?DownloadResponseBody $download = null,
        public readonly ?SentRequest $sent = null,
        public readonly ?InsightData $insight = null,
    ) {}
}
