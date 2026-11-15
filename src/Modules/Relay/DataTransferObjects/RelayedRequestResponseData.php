<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Magic\DataTransferObjects\InsightData;

class RelayedRequestResponseData
{
    /**
     * @param  string[][]  $headers
     * @param  ResponseCookieData[]  $cookies
     * @param  RedirectHopData[]  $redirects  Hops that answered with a redirect, in the order they were followed.
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $statusText,
        public readonly PrintableResponseBodyData $body,
        public readonly array $headers,
        public readonly float $durationMs,
        public readonly int $timestamp,
        public readonly array $cookies,
        public readonly ?DownloadResponseBodyData $download = null,
        public readonly ?SentRequestData $sent = null,
        public readonly ?InsightData $insight = null,
        public readonly array $redirects = [],
    ) {}
}
