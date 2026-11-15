<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\DataTransferObjects;

use Sunchayn\Nimbus\Modules\Relay\ValueObjects\SentRequest;

readonly class RedirectHopData
{
    /**
     * @param  array<string, string[]>  $headers
     */
    public function __construct(
        public SentRequest $request,
        public int $statusCode,
        public string $statusText,
        public array $headers,
    ) {}
}
