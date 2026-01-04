<?php

namespace Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Sunchayn\Nimbus\Modules\Relay\Parsers\VarDumpParser\Enums\DumpValueTypeEnum;

/**
 * @implements Arrayable<string, string|float|int|bool|null|array>
 */
readonly class ParsedValueDto implements Arrayable
{
    public function __construct(
        public DumpValueTypeEnum $type,
        public ParsedArrayResultDto|ParsedObjectResultDto|ParsedClosureResultDto|string|float|int|bool|null $value,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value instanceof Arrayable ? $this->value->toArray() : $this->value,
        ];
    }
}
