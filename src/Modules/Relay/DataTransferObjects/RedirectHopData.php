<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

readonly class RedirectHopData
{
    /**
     * @param  array<string, string[]>  $headers
     */
    public function __construct(
        public SentRequestData $request,
        public int $statusCode,
        public string $statusText,
        public array $headers,
    ) {}
}
