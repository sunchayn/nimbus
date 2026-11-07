<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\DataTransferObjects;

readonly class CreateSignedUrlData
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $route,
        public array $parameters,
        public ?int $expiresInMinutes,
    ) {}
}
