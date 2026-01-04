<?php

namespace Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|array>
 */
readonly class ObjectPropertyDto implements Arrayable
{
    public function __construct(
        public string $visibility,
        public ParsedValueDto $value,
    ) {}

    public function toArray(): array
    {
        return [
            'visibility' => $this->visibility,
            'value' => $this->value->toArray(),
        ];
    }
}
