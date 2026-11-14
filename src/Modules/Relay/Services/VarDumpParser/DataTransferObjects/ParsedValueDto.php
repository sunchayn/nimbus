<?php

namespace Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Sunchayn\Nimbus\Modules\Relay\Services\VarDumpParser\Enums\DumpValueTypeEnum;

/**
 * @phpstan-type ParsedValueShape array{type: string, value: array<string, mixed>|string|float|int|bool|null}
 *
 * @implements Arrayable<string, string|array<string, mixed>|float|int|bool|null>
 */
readonly class ParsedValueDto implements Arrayable
{
    public function __construct(
        public DumpValueTypeEnum $type,
        public ParsedArrayResultDto|ParsedObjectResultDto|ParsedClosureResultDto|string|float|int|bool|null $value,
    ) {}

    /**
     * @return ParsedValueShape
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value instanceof Arrayable ? $this->value->toArray() : $this->value,
        ];
    }
}
