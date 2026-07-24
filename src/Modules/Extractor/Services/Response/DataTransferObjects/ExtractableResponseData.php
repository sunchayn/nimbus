<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\Services\Response\DataTransferObjects;

use Illuminate\Routing\Route;

/**
 * Carries the data needed by response schema strategies to extract a Schema.
 */
class ExtractableResponseData
{
    /**
     * @param  array<string, mixed>|null  $payload  Concrete payload that can be used to help inferring the schema.
     */
    public function __construct(
        public readonly ?Route $route,
        public readonly ?array $payload,
    ) {}

    public static function empty(): self
    {
        return new self(
            route: null,
            payload: null,
        );
    }
}
