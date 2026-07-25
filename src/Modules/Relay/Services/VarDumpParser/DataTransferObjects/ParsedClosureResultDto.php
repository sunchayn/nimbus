<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|null>
 */
readonly class ParsedClosureResultDto implements Arrayable
{
    public function __construct(
        public string $signature,
        public ?string $className,
        public ?string $thisReference,
    ) {}

    public function toArray(): array
    {
        return [
            'signature' => $this->signature,
            'class' => $this->className,
            'this' => $this->thisReference,
        ];
    }
}
